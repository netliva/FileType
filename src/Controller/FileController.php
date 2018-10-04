<?php
namespace Netliva\FileTypeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
	public function show($file_name): Response
	{
		$fileExt = $this->get("netliva.file.upload_helper");
		$file = $fileExt->getUploadPath().DIRECTORY_SEPARATOR.$file_name;

		if (file_exists($file) and !is_dir($file))
		{
			$response = new Response(file_get_contents($file));
			$response->headers->set('Content-Type', mime_content_type($file));
			return $response;
		}

		throw $this->createNotFoundException('Dosya Bulunamadı.');
	}

}