<?php
// src/Routing/ExtraLoader.php
namespace Netliva\FileTypeBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FileLoader extends Loader
{
	private $isLoaded = false;
	private $container;
	public function __construct ($container) {
		$this->container = $container;
	}

	public function load($resource, $type = null)
	{
		if (true === $this->isLoaded) {
			throw new \RuntimeException('Do not add the "extra" loader twice');
		}

		$routes = new RouteCollection();

		$config = $this->container->getParameter('netliva.file_config');


		$route = new Route($config["download_uri"]."/{file_name}", ['_controller' => 'Netliva\FileTypeBundle\Controller\FileController::show'], []);
		$routes->add('netliva_file', $route);
		$route = new Route("/netliva/file/upload", ['_controller' => 'Netliva\FileTypeBundle\Controller\FileController::upload'], []);
		$routes->add('netliva_upload', $route);
		$route = new Route("/netliva/file/list", ['_controller' => 'Netliva\FileTypeBundle\Controller\FileController::getFiles'], []);
		$routes->add('netliva_file_list', $route);

		$this->isLoaded = true;

		return $routes;
	}

	public function supports($resource, $type = null)
	{
		return 'netliva_file_route' === $type;
	}
}