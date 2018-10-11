<?php
namespace Netliva\FileTypeBundle\Form\Type;

use Netliva\FileTypeBundle\Service\NetlivaDirectory;
use Netliva\FileTypeBundle\Service\NetlivaFile;
use Netliva\FileTypeBundle\Service\UploadHelperService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;


class NetlivaFileType extends AbstractType
{
	private $uploadHelperService;
	private $file_before_submit = [];
	private $moved_file = [];

	public function __construct (UploadHelperService $uploadHelperService) {

		$this->uploadHelperService = $uploadHelperService;
	}

	public function buildForm (FormBuilderInterface $builder, array $options)
	{

		// DB'den veriyi çekerken
		$getDataFromModel = function ($data) use ($builder)
		{
			$returnData = null;

			if (is_string($data) and preg_match("/\|/",$data))
				$data = explode("|",$data);

			$is_dir = false;
			if (is_array($data) and count($data) and !key_exists("filename", $data)) $is_dir = true;

			if ($is_dir)
			{
				$returnData = new NetlivaDirectory();
				foreach ($data as $d)
				{
					$file = $this->_createNetlivaFile($d);
					if($file)
						$returnData->addFile($file);
				}
			}
			else
			{
				$returnData = $this->_createNetlivaFile($data);
			}

			$this->file_before_submit[$builder->getName()] = $returnData;
			return $returnData;
		};

		// Veriyi Forma Eklerken
		$setDataToView = function($data) use ($builder)
		{

			if (is_array($data)) return null;

			return $data;
		};

		// Veriyi Formdan Alırken
		$getDataFromView = function($data) use ($builder)
		{
			if (!$data and $this->file_before_submit[ $builder->getName() ])
			{
				$data = $this->file_before_submit[ $builder->getName() ];
			}

			return $data;
		};

		// DB'ye Kaydederken
		$setDataToModel = function ($data) use ($builder, $options)
		{
			if (is_array($data))
			{
				$dir = new NetlivaDirectory();
				$dir->reset();
				foreach ($data as $k => $f)
				{
					$f = $this->_dataTrans($f, $builder, $options);
					if ($f instanceof NetlivaFile)
						$dir->addFile($f);
				}
				return $dir;
			}
			else $data = $this->_dataTrans($data, $builder, $options);

			return $data;
		};

		$evetnPreSubmit = function(FormEvent $event) use ($options, $builder) {
			$form           = $event->getForm();
			$requestHandler = $form->getConfig()->getRequestHandler();

			// formdan delete değeri gönderildiyse, veritabanındaki değeri silmek için önceki değeri sil
			if ($event->getData() == "delete")
			{
				$this->file_before_submit[ $builder->getName() ] = null;
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

	public function buildView (FormView $view, FormInterface $form, array $options)
	{
		$view->vars['types'] = $options['types'];
		$view->vars['deletable'] = $options['deletable'];

		if ($options['multiple']) {
			$view->vars['full_name'] .= '[]';
			$view->vars['attr']['multiple'] = 'multiple';
		}
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
			'types'			=> null,
			'deletable'		=> true,
			'unique_name'	=> false,
			'compound' 		=> false,
			'data_class' 	=> $dataClass,
			'empty_data' 	=> $emptyData,
			'multiple' 		=> false,
		]);
	}

	public function getBlockPrefix ()
	{
		return 'netliva_file_type';
	}

	public function getParent ()
	{
		return TextType::class;
	}



	private function _createNetlivaFile($data)
	{
		$oriName = null;
		if (is_array($data) and key_exists("original_name",$data))
			$oriName = $data["original_name"];

		$path = $this->uploadHelperService->getFilePath($data);
		if (file_exists($path))
			return new NetlivaFile($path, $this->uploadHelperService, $oriName);

		return null;

	}
	private function _dataTrans($data, $builder, $options)
	{
		if ($data instanceof UploadedFile and !($data instanceof NetlivaFile))
		{
			if ($options["unique_name"]) $fileName = $this->uploadHelperService->generateUniqueFileName($builder->getName()) . '.' . $data->guessExtension();
			else $fileName = $this->_sanitize($data->getClientOriginalName(), true);

			$fileName = $this->_renameIfExist($fileName);


			$data->move($this->uploadHelperService->getUploadPath(), $fileName);

			$path = $this->uploadHelperService->getUploadPath() . DIRECTORY_SEPARATOR . $fileName;
			if (file_exists($path))
			{
				$data = new NetlivaFile($path, $this->uploadHelperService, $data->getClientOriginalName());
				$this->moved_file[$builder->getName()] = $data;
			}
		}
		return $data;
	}

	private function _renameIfExist($filename)
	{
		$path = $this->uploadHelperService->getUploadPath();

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