<?php
namespace Netliva\FileTypeBundle\Controller;

use Netliva\FileTypeBundle\Form\Type\NetlivaFileType;
use Netliva\FileTypeBundle\Service\NetlivaDirectory;
use Netliva\MediaLibBundle\Entity\Files;
use Netliva\MediaLibBundle\Form\FormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

	public function getFiles(Request $request): JsonResponse
	{
		$em = $this->getDoctrine()->getManager();
		$files = $em->getRepository("NetlivaMediaLibBundle:Files")->findAll();
		$fileExt = $this->get("netliva.file.upload_helper");

		$data = [];
		foreach ($files as $file)
		{
			$data[] = [
				"id" => $file->getId(),
				"url" => $fileExt->getFileUri($file->getFileInfo()),
			];
		}
		
		return new JsonResponse($data);
	}

	public function upload(Request $request): JsonResponse
	{
		$upload_success = null;
		$upload_error = '';

		$form = $this->createForm(FormType::class);
		$form->handleRequest($request);

		$em = $this->getDoctrine()->getManager();

		if ($form->isSubmitted() && $form->isValid())
		{
			$dir = $form->get("nmlb-file")->getData();
			$returnFiles = [];
			if ($dir instanceof NetlivaDirectory)
			{
				foreach ($dir->getFiles() as $file)
				{
					$fe = new Files();
					$fe->setAddAt(new \DateTime());
					$fe->setTitle(pathinfo($file->getOriginalName(),PATHINFO_FILENAME));
					$fe->setFileInfo($file);
					$em->persist($fe);
					$em->flush();

					$returnFiles [$fe->getId()] = $file;
				}
			}
			return new JsonResponse([ 'success'=> true, 'files'=> $returnFiles]);
		}

		return new JsonResponse([ 'success'=> false ]);
	}

}