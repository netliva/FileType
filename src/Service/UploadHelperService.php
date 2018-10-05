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
			new \Twig_SimpleFilter('nl_file_uri', [$this, 'getFileUri']),
			new \Twig_SimpleFilter('nl_file_name', [$this, 'getFileName']),
		);
	}
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('nl_file', [$this, 'getNetlivaFile']),
		);
	}

	public function getFileName($file)
	{

		switch ($file){
			case is_string($file):
				return $file;
				break;
			case is_object($file) and method_exists($file, "getFilename"):
				return $file->getFileName();
				break;
			case is_array($file) and key_exists("filename", $file):
				return $file["filename"];
				break;
			default:
				return null;
				break;
		}
	}

	public function getFileUri($file)
	{
		$fileName = $this->getFileName($file);
		if($fileName)
		{
			$config = $this->container->getParameter('netliva.file_config');
			return $config["upload_uri"]."/".$fileName;
		}
		return null;
	}

	public function getNetlivaFile($file)
	{
		$fileName = $this->getFileName($file);
		if($fileName)
		{
			return new NetlivaFile($this->getUploadPath().DIRECTORY_SEPARATOR.$fileName, $this);
		}
		return null;
	}

	public function getFilePath($file)
	{
		$fileName = $this->getFileName($file);
		if($fileName)
		{
			$path = $this->getUploadPath().DIRECTORY_SEPARATOR.$fileName;
			if (file_exists($path))
				return $path;
		}
		return null;
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