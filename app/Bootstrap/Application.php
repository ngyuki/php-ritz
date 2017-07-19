<?php
namespace App\Bootstrap;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewarePipe;
use Ritz\Bootstrap\Server;
use Ritz\Middleware\DispatchMiddleware;
use Ritz\Middleware\RenderMiddleware;
use Ritz\Middleware\RouteMiddleware;
use App\Middleware\ErrorMiddleware;
use App\Middleware\LoginMiddleware;
use Franzl\Middleware\Whoops\PSR15Middleware as WhoopsMiddleware;

class Application implements MiddlewareInterface
{
    public static function main()
    {
        $container = (new ContainerFactory())->create();
        $server = new Server();
        $server->run($container->get(Application::class), $container->get('debug'));
    }

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
