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

class NetlivaFile extends UploadedFile implements \JsonSerializable
{

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @var string
	 */
	private $fileType;

	/**
	 * @var string|null
	 */
	private $originalName;

	public function __toString ()
	{
		return $this->getFilename();
	}

	public function __construct (string $path, UploadHelperService $uploadHelperService, $originalName = null) {
		parent::__construct($path, $uploadHelperService->getFileName($this), $this?mime_content_type($path):null, null, true);

		$mime = explode("/",$this->getMimeType());
		
		$this->setUri($uploadHelperService->getFileUri($this));
		$this->setFileType($mime[0]);
		$this->setOriginalName($originalName);
	}


	public function jsonSerialize()
	{

		return [
			"filename"		=> $this->getFilename(),
			"mimeType"		=> $this->getMimeType(),
			"type" 			=> $this->getType(),
			"extension"		=> $this->getExtension(),
			"path" 			=> $this->getPath(),
			"pathName"		=> $this->getPathname(),
			"uri"			=> $this->getUri(),
			"originalName"	=> $this->originalName,
		 ];
	}

	/**
	 * @return string
	 */
	public function getUri (): string
	{
		return $this->uri;
	}

	/**
	 * @param string $uri
	 */
	public function setUri (string $uri): void
	{
		$this->uri = $uri;
	}


	/**
	 * @return string
	 */
	public function getFileType (): string
	{
		return $this->fileType;
	}

	/**
	 * @param string $fileType
	 */
	public function setFileType (string $fileType): void
	{
		$this->fileType = $fileType;
	}
	
	/**
	 * @return string
	 */
	public function getOriginalName (): string
	{
		return $this->originalName;
	}

	/**
	 * @param string|null $originalName
	 */
	public function setOriginalName ($originalName): void
	{
		$this->originalName = $originalName;
	}

}