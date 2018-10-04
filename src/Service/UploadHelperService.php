<?php
/**
 * Created by PhpStorm.
 * User: msthzn
 * Date: 25.07.2018
 * Time: 08:57
 */

namespace Netliva\FileTypeBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadHelperService extends \Twig_Extension
{
	private $container;

	public function __construct (ContainerInterface $container) {
		$this->container = $container;
	}

	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('file_path', [$this, 'getFilePath']),
		);
	}
	public function getFunctions()
	{
		return array(
		);
	}

	public function getFilePath($file)
	{
		$config = $this->container->getParameter('netliva.file_config');
		return $config["upload_uri"]."/".$file->getFileName();
	}

	public function getUploadPath()
	{
		$config = $this->container->getParameter('netliva.file_config');
		return $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.$config["upload_dir"];
	}

	public function generateUniqueFileName($prefix)
	{
		return $prefix.'-'.md5(uniqid());
	}
}