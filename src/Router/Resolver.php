<?php
namespace ngyuki\Ritz\Router;

use Psr\Container\ContainerInterface;

class Resolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolve($route)
    {
        list ($class, $action) = $route;
        $controller = $class;
        $controller = preg_replace('/Controller(\\\\|$)/', '', $controller);
        $controller = str_replace('\\', DIRECTORY_SEPARATOR, $controller);
        $method = "{$action}Action";
        $template = "$controller/$action";
        $instance = $this->container->get($class);
        return new RouteResult($instance, $method, $template);
    }
}
