<?php
namespace Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ritz\Dispatcher\ActionInvoker;
use Ritz\Router\RouteResult;

class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ActionInvoker
     */
    private $invoker;

    public function __construct(ContainerInterface $container, ActionInvoker $invoker)
    {
        $this->container = $container;
        $this->invoker = $invoker;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class);

        if ($route === null) {
            return $handler->handle($request);
        }

        $instance = $route->getInstance();
        $method = $route->getMethod();

        if (is_string($instance)) {
            $instance = $this->container->get($instance);
        }

        if ($instance === null) {
            return $handler->handle($request);
        }

        $response = $this->invoker->invoke($request, $handler, $instance, $method);
        return $response;
    }
}
