<?php

namespace Netliva\FileTypeBundle\Service;


use Netliva\MediaLibBundle\Service\NetlivaMediaFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NetlivaDirectory implements \JsonSerializable
{
	/**
	 * @var boolean
	 */
	private $netliva_dir = true;


	/**
	 * @var NetlivaFile[]
	 */
	private $files = [];
	/**
	 * @return NetlivaFile[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	/**
	 * @param NetlivaFile $file
	 */
	public function addFile (NetlivaFile $file): NetlivaDirectory
	{
		$this->files[] = $file;

		return $this;
	}

	public function reset (): NetlivaDirectory
	{
		$this->files = [];

		return $this;
	}

	public function __toString (): string
	{
		$data = [];
		$type = null;
		foreach ($this->getFiles() as $file)
		{
			if ($file instanceof NetlivaMediaFile)
			{
				$data[$file->getEntity()->getId()] = $file->getFilename();
				$type = "media";
			}
			else
			{
				$data[] = $file->getFilename();
				$type = "file";
			}
		}

		if ($type == "media") return json_encode($data);
		else if ($type == "media") return implode("|", $data);

		return "";
	}

	public function jsonSerialize()
	{
		return $this->getFiles();

		$arr = [];
		foreach ($this->getFiles() as $file)
		{
			$arr[] = $file->jsonSerialize();
		}
		return $arr;
	}


}