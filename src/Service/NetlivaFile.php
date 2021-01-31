<?php
/**
 * Created by PhpStorm.
 * User: bilal
 * Date: 05.10.2018
 * Time: 08:18
 */

namespace Netliva\FileTypeBundle\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NetlivaFile implements \JsonSerializable
{
	/** @var string */
	private $filename;
	/** @var string */
	private $subfolder;
	/** @var string */
	private $path;


	public function __toString ()
	{
		return $this->getNameWithSubfolder();
	}

	public function __construct () { }


	public function jsonSerialize()
	{
		return [
			"filename"		=> $this->getFilename(),
			"path"  		=> $this->getPath(),
			"subfolder"		=> $this->getSubfolder(),
		 ];
	}

	public function getUploadedFile ()
	{
		if (!file_exists($this->getPath())) return null;

		return new UploadedFile($this->getPath(), $this->getFilename(), mime_content_type($this->getPath()));
	}

	public function getMimeType ()
	{
		if (!file_exists($this->getPath())) return null;
		return mime_content_type($this->getPath());
	}

	public function getFileType ()
	{
		if (!file_exists($this->getPath())) return null;

		return current(explode('/',mime_content_type($this->getPath())));

	}

	public function getExtension ()
	{
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}

	public function getNameWithSubfolder ()
	{
		if (!$this->getFilename()) return '';
		
		if ($this->getSubfolder())
			return $this->getSubfolder().DIRECTORY_SEPARATOR.$this->getFilename();

		return $this->getFilename();
	}

	/**
	 * @return string
	 */
	public function getFilename (): ?string
	{
		return $this->filename;
	}

	/**
	 * @param string $filename
	 */
	public function setFilename (string $filename): void
	{
		$this->filename = $filename;
	}


	/**
	 * @return string
	 */
	public function getSubfolder (): ?string
	{
		return $this->subfolder;
	}

	/**
	 * @param string $subfolder
	 */
	public function setSubfolder (?string $subfolder): void
	{
		$this->subfolder = $subfolder;
	}

	/**
	 * @return string
	 */
	public function getPath (): string
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath (string $path): void
	{
		$this->path = $path;
	}

}
