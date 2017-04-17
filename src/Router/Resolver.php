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
        list ($class, $method) = $route;
        $controller = $class;
        $controller = preg_replace('/Controller(\\\\|$)/', '', $controller);
        $controller = str_replace('\\', DIRECTORY_SEPARATOR, $controller);
        $action = preg_replace('/Action$/', '', $method);
        $template = "$controller/$action";
        $instance = $this->container->get($class);
        return new RouteResult($instance, $method, $template);
    }
}
