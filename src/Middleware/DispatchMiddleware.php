<?php
namespace Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
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

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $result = RouteResult::from($request);

        if ($result === null) {
            return $delegate->process($request);
        }

        $instance = $result->getInstance();
        $method = $result->getMethod();

        if (is_string($instance)) {
            $instance = $this->container->get($instance);
        }

        if ($instance === null) {
            return $delegate->process($request);
        }

        $response = $this->invoker->invoke($request, $delegate, $instance, $method);
        return $response;
    }
}
