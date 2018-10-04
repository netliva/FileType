<?php
/**
 * Created by PhpStorm.
 * User: msthzn
 * Date: 25.07.2018
 * Time: 08:57
 */

namespace Netliva\FileTypeBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadHelperService extends \Twig_Extension
{
	private $container;

	public function __construct (ContainerInterface $container) {
		$this->container = $container;
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('get_upload_dir', [$this, 'getUploadDir']),
		);
	}

	public function getUploadDir()
	{

		$config = $this->container->getParameter('netliva.file_config');

		return $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.$config["upload_dir"];
	}

	public function generateUniqueFileName($prefix)
	{
		return $prefix.'-'.md5(uniqid());
	}
}