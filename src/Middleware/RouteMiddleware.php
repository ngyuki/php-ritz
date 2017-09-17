<?php
namespace Ritz\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Ritz\Router\RouterInterface;
use Ritz\Router\RouteResult;

class RouteMiddleware implements MiddlewareInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $route = $this->router->route($request->getMethod(), $request->getUri()->getPath());

        if ($route === null) {
            return $delegate->process($request);
        }

        list ($handler, $params) = $route;

        if (count($handler) === 0) {
            return $delegate->process($request);
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $instance = $handler[0];

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
