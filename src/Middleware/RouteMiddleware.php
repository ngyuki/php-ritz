<?php
namespace Ritz\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Ritz\Router\Router;
use Ritz\Router\RouteResult;
use Ritz\View\TemplateResolver;
use Ritz\View\ViewModel;

class RouteMiddleware implements MiddlewareInterface
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var TemplateResolver
     */
    private $resolver;

    public function __construct(Router $router, ContainerInterface $container, TemplateResolver $resolver)
    {
        $this->router = $router;
        $this->container = $container;
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        list ($route, $params) = $this->router->route($request->getMethod(), $request->getUri()->getPath());

        if ($route === null) {
            return $delegate->process($request);
        }

        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        list ($class, $method) = $route;
        $instance = $this->container->get($class);
        $result = new RouteResult($instance, $method);
        $request = $request->withAttribute(RouteResult::class, $result);

        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            if ($response->getTemplate() === null) {
                $template = $this->resolver->resolve(get_class($result->getInstance()), $result->getMethod());
                if ($response->getRelative() !== null) {
                    $template = dirname($template) . '/' . $response->getRelative();
                }
                $response = $response->withTemplate($template);
            }
        }

        return $response;
    }
}
