<?php
namespace ngyuki\Ritz\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use ngyuki\Ritz\Router\Resolver;
use ngyuki\Ritz\Router\Router;
use ngyuki\Ritz\Router\RouteResult;
use ngyuki\Ritz\View\ViewModel;

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
                $response = $response->withTemplate($result->getTemplate());
            }
        }

        return $response;
    }
}


