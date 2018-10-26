<?php
/**
 * Created by PhpStorm.
 * User: msthzn
 * Date: 25.07.2018
 * Time: 08:57
 */

namespace Netliva\FileTypeBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Netliva\MediaLibBundle\Entity\Files;
use Netliva\MediaLibBundle\Service\NetlivaMediaFile;
use Symfony\Component\Asset\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadHelperService extends \Twig_Extension
{
	private $container;
	private $em;

	public function __construct (ContainerInterface $container, EntityManagerInterface $em) {
		$this->container = $container;
		$this->em = $em;
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
			new \Twig_SimpleFunction('get_nl_mfolder', [$this, 'getNetlivaMediaFolder']),
			new \Twig_SimpleFunction('get_nl_mfile', [$this, 'getNetlivaMediaFile']),
			new \Twig_SimpleFunction('get_nl_file', [$this, 'getNetlivaFile']),
		);
	}

	public function getFileName($file)
	{
		if(is_string($file))
			return $file;

		if(is_object($file) and method_exists($file, "getFilename"))
			return $file->getFileName();

		if(is_array($file) and key_exists("filename", $file))
			return $file["filename"];

		return null;
	}

	public function getFileUri($file)
	{
		$fileName = $this->getFileName($file);
		if($fileName)
		{
			$config = $this->container->getParameter('netliva.file_config');
			return $config["download_uri"]."/".$fileName;
		}
		return null;
	}

	public function getNetlivaFile($file)
	{
		$fileName = $this->getFileName($file);
		if($fileName)
		{
			$oriName = null;
			if (is_array($file) and key_exists("original_name",$file))
				$oriName = $file["original_name"];

			return new NetlivaFile($this->getUploadPath().DIRECTORY_SEPARATOR.$fileName, $this, $oriName);
		}
		return null;
	}

	public function getNetlivaMediaFolder($data)
	{
		if (is_string($data))
			$data = @json_decode($data);

		$returnData = new NetlivaFolder();

		if ($data)
		{
			foreach ($data as $mediaId => $info)
			{
				$file = $this->getNetlivaMediaFile($mediaId);
				if($file)
					$returnData->addFile($file);
			}
		}

		return $returnData;
	}

	public function getNetlivaMediaFile($mediaId)
	{
		if (!$mediaId) return null;
		elseif (is_array($mediaId) && key_exists("mediaId", $mediaId)) $mediaId = $mediaId["mediaId"];

		/** @var Files $file */
		$file = $this->em->getRepository("NetlivaMediaLibBundle:Files")->find($mediaId);
		if($file and $file->getFileInfo())
		{
			return new NetlivaMediaFile($mediaId, $this);
		}
		return null;
	}

	public function getMedia($mediaId)
	{
		if (!$mediaId) return null;
		elseif (is_array($mediaId) && key_exists("mediaId", $mediaId)) $mediaId = $mediaId["mediaId"];
		
		$file = $this->em->getRepository("NetlivaMediaLibBundle:Files")->find($mediaId);
		if($file and $file->getFileInfo())
		{
			return $file;
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