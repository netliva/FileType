<?php
namespace Netliva\FileTypeBundle;


use Netliva\FileTypeBundle\DependencyInjection\Compiler\FormThemePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NetlivaFileTypeBundle extends Bundle
{
	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	public function build(ContainerBuilder $container)
	{
		parent::build($container);
		$container->addCompilerPass(new FormThemePass());
	}

}