<?php
namespace ngyuki\Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Response\TextResponse;
use ngyuki\Ritz\View\ViewModel;
use DI\Container;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;

class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var Invoker
     */
    private $invoker;

    public function __construct(ContainerInterface $container)
    {
        if ($container instanceof Container) {
            $this->invoker = new Invoker(new ResolverChain([
                new TypeHintResolver(),
                new TypeHintContainerResolver($container),
                new AssociativeArrayResolver(),
            ]));
        }
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $instance = $request->getAttribute(Attribute::INSTANCE);
        $method = $request->getAttribute(Attribute::ACTION_METHOD);

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

            if ($this->invoker) {

                $parameters = [
                        ServerRequestInterface::class => $request,
                        DelegateInterface::class => $delegate,
                    ] + $request->getAttributes();

                $response = $this->invoker->call([$instance, $method], $parameters);

            } else {
                $response = $instance->$method($request, $delegate);
            }

            if (is_array($response)) {
                return new ViewModel($response);
            }

            if (is_string($response)) {
                return new TextResponse($response);
            }

            return $response;
        };

        $pipeline->pipe($action);

        return $pipeline->process($request, $delegate);
    }
}
