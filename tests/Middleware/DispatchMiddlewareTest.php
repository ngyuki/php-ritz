<?php
namespace Ritz\Test\Middleware;

use PHPUnit\Framework\TestCase;
use DI\ContainerBuilder;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Ritz\Middleware\DispatchMiddleware;
use Ritz\Dispatcher\ActionInvoker;
use Ritz\Router\RouteResult;

class DispatchMiddlewareTest extends TestCase
{
    private function createMiddleware()
    {
        $container = (new ContainerBuilder())->build();
        $middleware = new DispatchMiddleware($container, new ActionInvoker($container));
        return $middleware;
    }

    private function createDelegate()
    {
        $delegate = new CallableDelegateDecorator(
            function () { return (new Response())->withStatus(404); },
            new Response()
        );
        return $delegate;
    }

    /**
     * @test
     */
    function has_not_route_result()
    {
        $middleware = $this->createMiddleware();
        $delegate = $this->createDelegate();

        $request = ServerRequestFactory::fromGlobals();
        $response = $middleware->process($request, $delegate);

        assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    function has_not_instance()
    {
        $middleware = $this->createMiddleware();
        $delegate = $this->createDelegate();

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute(RouteResult::class, new RouteResult(null, 'xxx'));
        $response = $middleware->process($request, $delegate);

        assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    function has_instance()
    {
        $middleware = $this->createMiddleware();
        $delegate = $this->createDelegate();

        $instance = $this->getMockBuilder(\stdClass::class)->setMethods(['action'])->getMock();
        $instance->method('action')->willReturn((new Response())->withStatus(201));

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute(RouteResult::class, new RouteResult($instance, 'action'));
        $response = $middleware->process($request, $delegate);

        assertEquals(201, $response->getStatusCode());
    }
}
