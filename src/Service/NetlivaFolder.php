<?php

namespace Netliva\FileTypeBundle\Service;


use Netliva\MediaLibBundle\Service\NetlivaMediaFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NetlivaFolder implements \JsonSerializable
{
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
	public function addFile (NetlivaFile $file): NetlivaFolder
	{
		$this->files[] = $file;

		return $this;
	}

	public function reset (): NetlivaFolder
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
		else if ($type == "file") return implode("|", $data);

		return "";
	}

	public function jsonSerialize()
	{
		$arr = [];
		foreach ($this->getFiles() as $file)
		{
			if ($file instanceof NetlivaMediaFile)
			{
				$arr[$file->getEntity()->getId()] = $file->getFilename();
			}
			else
			{
				$arr[] = $file->jsonSerialize();
			}
		}
		return $arr;
	}


}