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

        foreach ($route->getParams() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $request->withAttribute(RouteResult::class, $route);

        return $delegate->process($request);
    }
}
