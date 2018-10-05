<?php
/**
 * Created by PhpStorm.
 * User: bilal
 * Date: 05.10.2018
 * Time: 08:18
 */

namespace Netliva\FileTypeBundle\Service;


use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NetlivaFile extends File implements \JsonSerializable
{

	/**
	 * @var string
	 */
	private $uri;

	public function __toString ()
	{
		return $this->getFilename();
	}

	public function __construct (string $path, UploadHelperService $uploadHelperService) {
		parent::__construct($path);

		$this->setUri($uploadHelperService->getFileUri($this));
	}


	public function jsonSerialize()
	{

		return [
			"filename"	=> $this->getFilename(),
			"mimeType"	=> $this->getMimeType(),
			"type" 		=> $this->getType(),
			"extension"	=> $this->getExtension(),
			"path" 		=> $this->getPath(),
			"pathName"	=> $this->getPathname(),
			"uri"		=> $this->getUri(),
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

	public function __wakeup ()
	{
		return [
			"filename"	=> $this->getFilename(),
			"mimeType"	=> $this->getMimeType(),
			"type" 		=> $this->getType(),
			"path" 		=> $this->getPath(),
			"pathName"	=> $this->getPathname(),
		];
	}
}