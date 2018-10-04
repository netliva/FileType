<?php
namespace Netliva\FileTypeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NetlivaFileType extends AbstractType
{

	public function buildForm (FormBuilderInterface $builder, array $options)
	{
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
		return FileType::class;
	}
}