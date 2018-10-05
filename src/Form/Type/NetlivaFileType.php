<?php
namespace Netliva\FileTypeBundle\Form\Type;

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

class NetlivaFileType extends AbstractType
{
	private $uploadHelperService;
	private $file_before_submit = [];
	private $moved_file = [];

	public function __construct (UploadHelperService $uploadHelperService) {

		$this->uploadHelperService = $uploadHelperService;
	}

	public function configureOptions (OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'types'	=> null,
			'deletable'	=> true,
		]);
	}

	public function buildForm (FormBuilderInterface $builder, array $options)
	{

		// DB'den veriyi çekerken
		$getDataFromModel = function ($file) use ($builder)
		{
			$netliva_file = null;
			$path = $this->uploadHelperService->getFilePath($file);
			if (file_exists($path))
				$netliva_file = new NetlivaFile($path, $this->uploadHelperService);

			$this->file_before_submit[$builder->getName()] = $netliva_file;
			return $netliva_file;
		};

		// Veriyi Forma Eklerken
		$setDataToView = function($file) use ($builder)
		{
			return $file;
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
		$setDataToModel = function ($data) use ($builder)
		{
			if ($data instanceof UploadedFile and !($data instanceof NetlivaFile))
			{
				$fileName = $this->uploadHelperService->generateUniqueFileName($builder->getName()) . '.' . $data->guessExtension();
				$data->move($this->uploadHelperService->getUploadPath(), $fileName);

				$path = $this->uploadHelperService->getUploadPath() . DIRECTORY_SEPARATOR . $fileName;
				if (file_exists($path))
				{
					$data = new NetlivaFile($path, $this->uploadHelperService);
					$this->moved_file[$builder->getName()] = $data;
				}
			}
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

			if (!$requestHandler->isFileUpload($event->getData()))
			{
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
	}

	public function finishView(FormView $view, FormInterface $form, array $options)
	{
		if ($form->isSubmitted() and !$form->getParent()->isValid() and key_exists($form->getName(), $this->moved_file))
		{
			@unlink($this->moved_file[$form->getName()]->getPathname());
		}
		$view->vars['multipart'] = true;
	}

	public function getBlockPrefix ()
	{
		return 'netliva_file_type';
	}

	public function getParent ()
	{
		return TextType::class;
	}



}