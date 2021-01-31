<?php
namespace Netliva\FileTypeBundle\Form\Type;

use Netliva\FileTypeBundle\Service\NetlivaFolder;
use Netliva\FileTypeBundle\Service\NetlivaFile;
use Netliva\FileTypeBundle\Service\UploadHelperService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;


class NetlivaFileType extends AbstractType
{
	private $uhs;
	private $request;
	private $moved_file = [];

	public function __construct (UploadHelperService $uploadHelperService, RequestStack $request) {
		$this->uhs = $uploadHelperService;
		$this->request = $request->getCurrentRequest()->request->all();
	}

	public function buildForm (FormBuilderInterface $builder, array $options)
	{
		$file_before_submit = null;

		// DB'den veriyi çekerken
		$getDataFromModel = function ($data) use ($builder, &$file_before_submit)
		{
			// dump('DBden veriyi çekerken', $data);
			if (is_string($data) and preg_match("/\|/",$data))
				$data = explode("|",$data);

			$is_dir = false;
			if (is_array($data) and count($data) and !key_exists("filename", $data)) $is_dir = true;

			if ($is_dir)
			{
				$returnData = new NetlivaFolder();
				foreach ($data as $d)
				{
					$file = $this->uhs->getNetlivaFile($d);
					if($file)
						$returnData->addFile($file);
				}
			}
			else
			{
				$returnData = $this->uhs->getNetlivaFile($data);
			}

			$file_before_submit = $returnData;
			return $returnData;
		};

		// Veriyi Forma Eklerken
		$setDataToView = function($data) use ($builder)
		{
			// dump('Veriyi Forma Eklerken', $data);
			if (is_array($data)) return null;

			if ($data instanceof NetlivaFile)
				return $data->getUploadedFile();

			return $data;
		};

		// Veriyi Formdan Alırken
		$getDataFromView = function($data) use ($builder, &$file_before_submit)
		{
			// dump('Veriyi Formdan Alırken', $data);
			if (!$data and $file_before_submit)
			{
				$data = $file_before_submit;
			}

			return $data;
		};

		// DB'ye Kaydederken
		$setDataToModel = function ($data) use ($builder, $options)
		{
			// dump('DBye Kaydederken', $data);
			if (is_array($data))
			{
				$dir = new NetlivaFolder();
				$dir->reset();
				foreach ($data as $k => $f)
				{
					$f = $this->_dataTrans($f, $builder, $options);
					if ($f instanceof NetlivaFile) $dir->addFile($f);
				}
				if ($options['return_data_type'] == 'string') return $dir->__toString();

				if ($options['return_data_type'] == 'array') return $dir->jsonSerialize();

				return $dir;
			}
			else $data = $this->_dataTrans($data, $builder, $options);

			if ($data instanceof NetlivaFile && $options['return_data_type'] == 'string')
				return $data->__toString();

			if ($data instanceof NetlivaFile && $options['return_data_type'] == 'array')
				return $data->jsonSerialize();

			return $data;
		};

		$evetnPreSubmit = function(FormEvent $event) use ($options, $builder, &$file_before_submit) {
			$form           = $event->getForm();
			$requestHandler = $form->getConfig()->getRequestHandler();

			// dump('PRE_SUBMIT', $event);
			// formdan delete değeri gönderildiyse, veritabanındaki değeri silmek için önceki değeri sil
			if ($this->_findRequest($form) == "delete")
			{
				$file_before_submit = null;
			}

			if ($options['multiple']) {
				$data = array();
				$files = $event->getData();

				if (!\is_array($files)) {
					$files = array();
				}

				foreach ($files as $file) {
					if ($requestHandler->isFileUpload($file)) {
						$data[] = $file;
					}
				}

				// Since the array is never considered empty in the view data format
				// on submission, we need to evaluate the configured empty data here
				if (array() === $data) {
					$emptyData = $form->getConfig()->getEmptyData();
					$data = $emptyData instanceof \Closure ? $emptyData($form, $data) : $emptyData;
				}

				$event->setData($data);
			} elseif (!$requestHandler->isFileUpload($event->getData())) {
				$event->setData(null);
			}
		};

		$builder
			->addEventListener(FormEvents::PRE_SUBMIT, $evetnPreSubmit)
			->addViewTransformer(new CallbackTransformer(
				$setDataToView, // Veriyi Forma Eklerken
				$getDataFromView  // Veriyi Formdan Alırken
			))
			->addModelTransformer(new CallbackTransformer(
				$getDataFromModel,  // DB'den veriyi çekerken
				$setDataToModel // DB'ye Kaydederken
			));
	}

	private function _findRequest (FormInterface  $form)
	{
		if ($form->getParent())
		{
			$req = $this->_findRequest($form->getParent());
			if (is_array($req) && key_exists($form->getName(), $req))
				return $req[$form->getName()];
		}

		if (key_exists($form->getName(), $this->request))
			return $this->request[$form->getName()];

	}

	public function buildView (FormView $view, FormInterface $form, array $options)
	{
		$view->vars['types'] = $options['types'];
		$view->vars['deletable'] = $options['deletable'];
		$view->vars['bootstrap'] = $options['bootstrap'];
	}

	public function finishView(FormView $view, FormInterface $form, array $options)
	{
		if ($form->isSubmitted() and !$form->getParent()->isValid() and key_exists($form->getName(), $this->moved_file))
		{
			@unlink($this->moved_file[$form->getName()]->getPathname());
		}
		$view->vars['multipart'] = true;
	}

	public function configureOptions (OptionsResolver $resolver)
	{
		$dataClass = null;
		if (class_exists('Symfony\Component\HttpFoundation\File\File')) {
			$dataClass = function (Options $options) {
				return $options['multiple'] ? null : 'Symfony\Component\HttpFoundation\File\File';
			};
		}

		$emptyData = function (Options $options) {
			return $options['multiple'] ? array() : null;
		};

		$resolver->setDefaults([
		   'types'            => null,
		   'return_data_type' => 'array',
		   'deletable'        => true,
		   'bootstrap'        => false,
		   'unique_name'      => false,
		   'compound'         => false,
		   'data_class'       => $dataClass,
		   'empty_data'       => $emptyData,
		   'multiple'         => false,
		   'sub_folder'           => null,
		]);
	}

	public function getBlockPrefix ()
	{
		return 'netliva_file_type';
	}

	public function getParent ()
	{
		return FileType::class;
	}


	private function _dataTrans($data, $builder, $options)
	{
		if ($data instanceof UploadedFile and !($data instanceof NetlivaFile))
		{
			if ($options["unique_name"]) $fileName = $this->uhs->generateUniqueFileName($builder->getName()) . '.' . $data->guessExtension();
			else $fileName = $this->_sanitize($data->getClientOriginalName(), true);

			$fileName = $this->_renameIfExist($options["sub_folder"], $fileName);

			$path = $this->uhs->getUploadPath();
			if ($options["sub_folder"])
			{
				$options["sub_folder"] = $this->uhs->trimPath($options["sub_folder"]);
				$path = $path.DIRECTORY_SEPARATOR.$options["sub_folder"];

			}

			$data->move($path, $fileName);

			$path = $path . DIRECTORY_SEPARATOR . $this->uhs->trimPath($fileName);
			if (file_exists($path))
			{
				$this->moved_file[$builder->getName()] = $data;

				$data = $this->uhs->createNetlivaFileFromPath($path, $options["sub_folder"]);
			}
		}
		return $data;
	}

	private function _renameIfExist($sub_folder, $filename)
	{
		$path = $this->uhs->getUploadPath();

		if ($sub_folder)
			$path = $path.DIRECTORY_SEPARATOR.$sub_folder;

		if (!file_exists($path.DIRECTORY_SEPARATOR.$filename)) return $filename;


		$fnameNoExt = pathinfo($filename,PATHINFO_FILENAME);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		$i = 1;
		while(file_exists($path.DIRECTORY_SEPARATOR."$fnameNoExt($i).$ext")) $i++;

		return "$fnameNoExt($i).$ext";
	}

	private function _sanitize($string = '', $is_filename = FALSE)
	{
		// Replace all weird characters with dashes
		$string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $string);

		// Only allow one dash separator at a time (and make string lowercase)
		return mb_strtolower(preg_replace('/--+/u', '-', $string), 'UTF-8');
	}
}
