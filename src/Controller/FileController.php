<?php
namespace Netliva\FileTypeBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Netliva\FileTypeBundle\Form\Type\NetlivaFileType;
use Netliva\FileTypeBundle\Service\NetlivaFolder;
use Netliva\FileTypeBundle\Service\UploadHelperService;
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
		$nfu = $this->get("netliva.file.upload_helper");
		$file = $nfu->getUploadPath().DIRECTORY_SEPARATOR.$file_name;

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
		$qb = $em->getRepository(Files::class)->createQueryBuilder('f');
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

		/** @var UploadHelperService $nfu */
		$nfu = $this->get("netliva.file.upload_helper");

		$data = [];
		foreach ($files as $file)
		{
			$netlivaFile = $nfu->getNetlivaFile($file->getFileInfo());
			$data[] = [
				"id" 		=> $file->getId(),
				"url" 		=> $nfu->getFileUri($netlivaFile),
				"filename" 	=> $netlivaFile->getFilename(),
				"data"		=> [
					"mimeType"		=> $netlivaFile->getMimeType(),
					"extension"		=> $netlivaFile->getExtension(),
					"title"			=> $file->getTitle(),
					"caption"		=> $file->getCaption(),
					"alt"			=> $file->getAlt(),
					"description"	=> $file->getDescription(),
					"addAt"			=> $file->getAddAt()->format("d.m.Y H:i"),
				]
			];
		}

		return new JsonResponse($data);
	}

	public function upload(Request $request): JsonResponse
	{
		$nfu  = $this->get("netliva.file.upload_helper");
		$form = $this->createForm(FormType::class);
		$form->handleRequest($request);

		$em = $this->getDoctrine()->getManager();

		if ($form->isSubmitted() && $form->isValid())
		{
			$dir = $form->get("nmlb-file")->getData();
			$returnFiles = [];
			if (is_array($dir))
			{
				foreach ($dir as $file)
				{
					$fe = new Files();
					$fe->setAddAt(new \DateTime());
					$fe->setTitle($file['filename']);
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
