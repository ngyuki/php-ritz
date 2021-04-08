<?php
namespace Ritz\Test\Middleware;

use PHPUnit\Framework\TestCase;
use DI\ContainerBuilder;
use Ritz\RequestHandler\FinalDelegate;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
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

    /**
     * @test
     */
    function has_not_route_result()
    {
        $middleware = $this->createMiddleware();

        $request = ServerRequestFactory::fromGlobals();
        $response = $middleware->process($request, new FinalDelegate());

        assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    function has_not_instance()
    {
        $middleware = $this->createMiddleware();

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute(RouteResult::class, new RouteResult(405, null, 'xxx', []));
        $response = $middleware->process($request, new FinalDelegate());

        assertEquals(405, $response->getStatusCode());
    }

    /**
     * @test
     */
    function has_instance()
    {
        $middleware = $this->createMiddleware();

        $instance = $this->getMockBuilder(\stdClass::class)->setMethods(['action'])->getMock();
        $instance->method('action')->willReturn((new Response())->withStatus(201));

        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute(RouteResult::class, new RouteResult(200, $instance, 'action', []));
        $response = $middleware->process($request, new FinalDelegate());

        assertEquals(201, $response->getStatusCode());
    }
}
