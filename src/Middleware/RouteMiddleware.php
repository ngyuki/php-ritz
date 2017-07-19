<?php
namespace Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Ritz\Router\Router;
use Ritz\Router\RouteResult;

class RouteMiddleware implements MiddlewareInterface
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(Router $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        list ($route, $params) = $this->router->route($request->getMethod(), $request->getUri()->getPath());

        if ($route === null) {
            return $delegate->process($request);
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        list ($class, $method) = $route;
        $instance = $this->container->get($class);
        $result = new RouteResult($instance, $method);
        $request = $request->withAttribute(RouteResult::class, $result);

        $response = $delegate->process($request);

        return $response;
    }
}
