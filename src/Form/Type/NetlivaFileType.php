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
	private $file_before_submit;

	public function __construct (UploadHelperService $uploadHelperService) {

		$this->uploadHelperService = $uploadHelperService;
	}

	private $fieldName;
	public function buildForm (FormBuilderInterface $builder, array $options)
	{
		$this->fieldName = $builder->getName();
		$builder

			->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
				$form = $event->getForm();
				$requestHandler = $form->getConfig()->getRequestHandler();

				if (!$requestHandler->isFileUpload($event->getData())) {
					$event->setData(null);
				}
			})
			->addModelTransformer(new CallbackTransformer(
				function ($file) // veriyi çekerken
				{
					$netliva_file = null;
					$path = $this->uploadHelperService->getFilePath($file);
					if (file_exists($path))
						$netliva_file = new NetlivaFile($path, $this->uploadHelperService);

					$this->file_before_submit = $netliva_file;
					return $netliva_file;
				},
				function ($data) use ($builder)  // kaydederken
				{
					if ($data instanceof UploadedFile)
					{
						$fileName = $this->uploadHelperService->generateUniqueFileName($this->fieldName).'.'.$data->guessExtension();
						$data->move($this->uploadHelperService->getUploadPath(), $fileName);

						$path = $this->uploadHelperService->getUploadPath().DIRECTORY_SEPARATOR.$fileName;
						if (file_exists($path))
						{
							return new NetlivaFile($path, $this->uploadHelperService);
						}
					}
					if (is_null($data) and $this->file_before_submit)
					{
						return $this->file_before_submit;
					}

					return null;
				}
			));
	}

	public function buildView (FormView $view, FormInterface $form, array $options)
	{
		$view->vars['types'] = $options['types'];
	}

	public function configureOptions (OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'types'	=> null,
		]);	}

	public function getBlockPrefix ()
	{
		return 'netliva_file_type';
	}

	public function getParent ()
	{
		return TextType::class;
	}
	/*
	*/
	public function finishView(FormView $view, FormInterface $form, array $options)
	{
		$view->vars['multipart'] = true;
	}
}