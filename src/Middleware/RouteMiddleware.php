<?php
namespace Ritz\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Ritz\Router\Resolver;
use Ritz\Router\Router;
use Ritz\Router\RouteResult;
use Ritz\View\ViewModel;

class RouteMiddleware implements MiddlewareInterface
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(Router $router, Resolver $resolver)
    {
        $this->router = $router;
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        list ($route, $params) = $this->router->route($method, $uri);

        if ($route === null) {
            $request = $request->withAttribute(RouteResult::class, new RouteResult(null, null, null));
            return $delegate->process($request);
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $result = $this->resolver->resolve($route);

        $request = $request->withAttribute(RouteResult::class, $result);

        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            if ($response->getTemplate() === null) {
                if ($response->getRelative() === null) {
                    $response = $response->withTemplate($result->getTemplate());
                } else {
                    $response = $response->withTemplate(dirname($result->getTemplate()) . '/' . $response->getRelative());
                }
            }
        }

        return $response;
    }
}
