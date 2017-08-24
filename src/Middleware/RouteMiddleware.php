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

        $handler = [];

        foreach ($route as $name => $value) {
            if (is_string($name)) {
                $request = $request->withAttribute($name, $value);
            } else {
                $handler[] = $value;
            }
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        if (count($handler) === 0) {
            return $delegate->process($request);
        }

        $instance = $handler[0];

        if (is_string($instance)) {
            $instance = $this->container->get($instance);
        }

        $method = null;

        if (count($handler) > 1) {
            $method = $handler[1];
        }

        $result = new RouteResult($instance, $method);
        $request = $request->withAttribute(RouteResult::class, $result);

        $response = $delegate->process($request);

        return $response;
    }
}
