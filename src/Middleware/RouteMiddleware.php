<?php
namespace Ritz\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->route($request->getMethod(), $request->getUri()->getPath());

        if ($route === null) {
            return $handler->handle($request);
        }

        foreach ($route->getParams() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $request->withAttribute(RouteResult::class, $route);

        return $handler->handle($request);
    }
}
