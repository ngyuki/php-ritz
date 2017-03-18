<?php
namespace ngyuki\Ritz\Router;

class Resolver
{
    public function resolve($route)
    {
        list ($class, $action) = $route;
        $controller = $class;
        $controller = preg_replace('/Controller(\\\\|$)/', '', $controller);
        $controller = str_replace('\\', DIRECTORY_SEPARATOR, $controller);
        $method = "{$action}Action";
        return [$controller, $action, $class, $method];
    }
}
