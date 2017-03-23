<?php
namespace ngyuki\Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Stratigility\MiddlewarePipe;
use ngyuki\Ritz\Dispatcher\ActionInvoker;

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
        $instance = RouteResult::from($request)->getInstance();
        $method = RouteResult::from($request)->getMethod();

        if ($instance === null) {
            return $delegate->process($request);
        }

        $pipeline = new MiddlewarePipe();

        if ($instance instanceof MiddlewareInterface) {
            $pipeline->pipe($instance);
        }

        if ($method === null) {
            return $pipeline->process($request, $delegate);
        }

        $action = function (ServerRequestInterface $request, DelegateInterface $delegate) use ($instance, $method) {
            $response = $this->invoker->invoke($request, $delegate, $instance, $method);
            return $response;
        };

        $pipeline->pipe($action);

        return $pipeline->process($request, $delegate);
    }
}
