<?php
namespace Netliva\FileTypeBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Netliva\FileTypeBundle\Form\Type\NetlivaFileType;
use Netliva\FileTypeBundle\Service\NetlivaFolder;
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
		/** @var QueryBuilder $qb */
		$qb = $em->getRepository("NetlivaMediaLibBundle:Files")->createQueryBuilder('f');
		$qb->orderBy("f.addAt","DESC");


		if ($request->request->get("search_text"))
		{
			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->like("f.title", ":search_text"),
					$qb->expr()->like("f.caption", ":search_text"),
					$qb->expr()->like("f.alt", ":search_text"),
					$qb->expr()->like("f.description", ":search_text")
				)
			)->setParameter("search_text", "%".$request->request->get("search_text")."%");
		}

		$query = $qb->getQuery();
		$files = $query->getResult();

		$fileExt = $this->get("netliva.file.upload_helper");

		$data = [];
		foreach ($files as $file)
		{
			$data[] = [
				"id" 		=> $file->getId(),
				"url" 		=> $fileExt->getFileUri($file->getFileInfo()),
				"filename" 	=> $fileExt->getFileName($file->getFileInfo()),
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
			if ($dir instanceof NetlivaFolder)
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