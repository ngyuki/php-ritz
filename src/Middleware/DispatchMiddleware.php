<?php
namespace Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Stratigility\MiddlewarePipe;
use Ritz\Dispatcher\ActionInvoker;
use Ritz\Router\RouteResult;

class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var ActionInvoker
     */
    private $invoker;

    public function __construct(ContainerInterface $container, ActionInvoker $invoker)
    {
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

        if ($instance === null) {
            return $delegate->process($request);
        }

        $action = function (ServerRequestInterface $request, DelegateInterface $delegate) use ($instance, $method) {
            $response = $this->invoker->invoke($request, $delegate, $instance, $method);
            return $response;
        };

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe($action);

        return $pipeline->process($request, $delegate);
    }
}
