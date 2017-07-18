<?php
namespace App;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewarePipe;
use ngyuki\Ritz\Middleware\DispatchMiddleware;
use ngyuki\Ritz\Middleware\RenderMiddleware;
use ngyuki\Ritz\Middleware\RouteMiddleware;
use App\Middleware\ErrorMiddleware;
use App\Middleware\LoginMiddleware;
use Franzl\Middleware\Whoops\PSR15Middleware as WhoopsMiddleware;

class Application implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $pipeline = new MiddlewarePipe();

        if ($this->container->get('debug')) {
            $pipeline->pipe(new WhoopsMiddleware());
        }
        $pipeline->pipe($this->container->get(RenderMiddleware::class));
        $pipeline->pipe($this->container->get(ErrorMiddleware::class));
        if ($this->container->get('debug')) {
            $pipeline->pipe(new WhoopsMiddleware());
        }
        $pipeline->pipe($this->container->get(RouteMiddleware::class));
        $pipeline->pipe($this->container->get(LoginMiddleware::class));
        $pipeline->pipe($this->container->get(DispatchMiddleware::class));

        return $pipeline->process($request, $delegate);
    }
}
