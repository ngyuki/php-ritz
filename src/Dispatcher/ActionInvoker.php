<?php
namespace Ritz\Dispatcher;

use Psr\Container\ContainerInterface;
use Interop\Container\ContainerInterface as InteropContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;
use Zend\Diactoros\Response\TextResponse;
use Ritz\View\ViewModel;

class ActionInvoker
{
    /**
     * @var Invoker
     */
    private $internalInvoker;

    public function __construct(ContainerInterface $container)
    {
        if ($container instanceof InteropContainerInterface) {
            $chain = [
                new TypeHintResolver(),
                new TypeHintContainerResolver($container),
                new AssociativeArrayResolver(),
            ];
        } else {
            $chain = [
                new TypeHintResolver(),
                new AssociativeArrayResolver(),
            ];
        }

        $this->internalInvoker = new Invoker(new ResolverChain($chain));
    }

    public function invoke(ServerRequestInterface $request, DelegateInterface $delegate, $instance, $method)
    {
        $parameters = [
            ServerRequestInterface::class => $request,
            DelegateInterface::class => $delegate,
        ];

        $parameters += $request->getAttributes();

        $response = $this->internalInvoker->call([$instance, $method], $parameters);

        if (is_array($response)) {
            return new ViewModel($response);
        }

        if (is_string($response)) {
            return new TextResponse($response);
        }

        return $response;

    }
}
