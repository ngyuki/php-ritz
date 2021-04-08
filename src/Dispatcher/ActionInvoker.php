<?php
namespace Ritz\Dispatcher;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;
use Psr\Http\Server\RequestHandlerInterface;
use Ritz\View\ViewModel;

class ActionInvoker
{
    /**
     * @var Invoker
     */
    private $internalInvoker;

    public function __construct(ContainerInterface $container)
    {
        $chain = [
            new TypeHintResolver(),
            new TypeHintContainerResolver($container),
            new AssociativeArrayResolver(),
        ];

        $this->internalInvoker = new Invoker(new ResolverChain($chain));
    }

    public function invoke(ServerRequestInterface $request, RequestHandlerInterface $handler, $instance, $method)
    {
        $parameters = [
            ServerRequestInterface::class => $request,
            RequestHandlerInterface::class => $handler,
        ];

        $parameters += $request->getAttributes();

        if ($method === null) {
            $response = $this->internalInvoker->call($instance, $parameters);
        } else {
            $response = $this->internalInvoker->call([$instance, $method], $parameters);
        }

        if (is_array($response)) {
            return new ViewModel($response);
        }

        if (is_string($response)) {
            return new TextResponse($response);
        }

        return $response;

    }
}
