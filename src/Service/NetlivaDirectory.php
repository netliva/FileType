<?php

namespace Netliva\FileTypeBundle\Service;


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
		$text = "";
		$say = 0;
		foreach ($this->getFiles() as $file)
		{
			if($say) $text .= "|";
			$text .= $file->getFilename();
			$say++;
		}
		return $text;
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