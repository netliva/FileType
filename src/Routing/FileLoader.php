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

		// prepare a new route
		$defaults = array(
			'_controller' => 'Netliva\FileTypeBundle\Controller\FileController::show',
		);

		$requirements = array(
		//	'file_name' => '\.+',
		);
		$route = new Route($config["upload_uri"]."/{file_name}", $defaults, $requirements);

		// add the new route to the route collection
		$routeName = 'extraRoute';
		$routes->add($routeName, $route);

		$this->isLoaded = true;

		return $routes;
	}

	public function supports($resource, $type = null)
	{
		return 'netliva_file_route' === $type;
	}
}