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
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UploadHelperService extends AbstractExtension
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
			new TwigFilter('nl_file_uri', [$this, 'getFileUri']),
			new TwigFilter('nl_file_name', [$this, 'getFileName']),
		);
	}
	public function getFunctions()
	{
		return array(
			new TwigFunction('get_nl_mfolder', [$this, 'getNetlivaMediaFolder']),
			new TwigFunction('get_nl_mfile', [$this, 'getNetlivaMediaFile']),
			new TwigFunction('get_nl_file', [$this, 'getNetlivaFile']),
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
		$file = $this->getNetlivaFile($file);
		if($file)
		{
			$config = $this->container->getParameter('netliva.file_config');
			return $config["download_uri"]."/".$file->getNameWithSubfolder();
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

		if (!is_numeric($mediaId) and @json_decode($mediaId))
		{
			$mediaId = json_decode($mediaId, true);
		}

		if (is_array($mediaId) && key_exists("mediaId", $mediaId)) $mediaId = $mediaId["mediaId"];
		if (is_array($mediaId) && is_numeric(array_keys($mediaId)[0])) $mediaId = array_keys($mediaId)[0];

		$mediaEntity = $this->getMedia($mediaId);
		if ($mediaEntity)
		{
			$fileInfo = $this->getNetlivaFile($mediaEntity->getFileInfo());
			$file = new NetlivaMediaFile();
			$file->setEntity($mediaEntity);
			$file->setVars($fileInfo);
			return $file;
		}
		return null;
	}

	public function getMedia($mediaId):?Files
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
		$file = $this->getNetlivaFile($file);
		if($file)
		{
			$path = $this->getUploadPath().DIRECTORY_SEPARATOR.$file->getNameWithSubfolder();
			if (file_exists($path))
				return $path;
		}
		return null;
	}

	public function getUploadPath()
	{
		$config = $this->container->getParameter('netliva.file_config');
		return $config["upload_dir"];
	}

	public function generateUniqueFileName($prefix)
	{
		return $prefix.'-'.md5(uniqid());
	}

	public function createNetlivaFileFromPath($path, $subFolder = null)
	{
		if (file_exists($path))
		{
			$uploadPath = $this->getUploadPath();

			$fileName = $this->trimPath(substr($path,strlen($this->trimPath($uploadPath))));
			if ($subFolder)
			{
				$subFolder = $this->trimPath($subFolder);
				$fileName = $this->trimPath(substr($fileName,strlen($this->trimPath($subFolder))));
			}
			$file = new NetlivaFile();
			$file->setPath($path);
			$file->setSubfolder($subFolder);
			$file->setFilename($fileName);
			return $file;
		}

		return null;
	}

	public function getNetlivaFile ($dbInfo) :?NetlivaFile
	{
		if( $dbInfo instanceof NetlivaFile) return $dbInfo;

		if (is_array($dbInfo) && key_exists('filename', $dbInfo))
		{
			$path = key_exists('path', $dbInfo) ? $dbInfo['path'] : '';
			if (key_exists('subfolder', $dbInfo) && key_exists('filename', $dbInfo))
				$path = $this->getUploadPath().DIRECTORY_SEPARATOR.$dbInfo['subfolder'].DIRECTORY_SEPARATOR.$dbInfo['filename'];

			$file = new NetlivaFile();
			$file->setPath($path);
			$file->setSubfolder(key_exists('subfolder', $dbInfo) ? $dbInfo['subfolder'] : '');
			$file->setFilename($dbInfo['filename']);
			return $file;
		}

		if (is_string($dbInfo))
		{
			$expl = explode(DIRECTORY_SEPARATOR, $dbInfo);
			$filename = array_pop($expl);
			$subfolder = implode(DIRECTORY_SEPARATOR, $expl);

			$file = new NetlivaFile();
			$file->setPath($this->getUploadPath().DIRECTORY_SEPARATOR.$subfolder.DIRECTORY_SEPARATOR.$filename);
			$file->setSubfolder($subfolder);
			$file->setFilename($filename);

			return $file;
		}

		return null;
	}

	public function trimPath ($path)
	{
		return trim($path, DIRECTORY_SEPARATOR);
	}

}
