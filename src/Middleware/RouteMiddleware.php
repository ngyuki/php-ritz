<?php
namespace ngyuki\Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use ngyuki\Ritz\Router\Resolver;
use ngyuki\Ritz\Router\Router;
use ngyuki\Ritz\View\ViewModel;

class RouteMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Resolver
     */
    private $resolver;

    public function __construct(ContainerInterface $container, Router $router, Resolver $resolver)
    {
        $this->container = $container;
        $this->router = $router;
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        list ($route, $params) = $this->router->route($method, $uri);

        if ($route === null) {
            return $delegate->process($request);
        }

        list ($controller, $action, $class, $method) = $this->resolver->resolve($route);

        if ($class === null) {
            return $delegate->process($request);
        }

        $instance = $this->container->get($class);

        ///

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        /* @var $request ServerRequestInterface */
        $request = $request->withAttribute(Attribute::ACTION_METHOD, $method);
        $request = $request->withAttribute(Attribute::INSTANCE, $instance);

        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            if ($response->getTemplate() === null) {
                $response = $response->withTemplate("$controller/$action");
            }
        }

        return $response;
    }
}


