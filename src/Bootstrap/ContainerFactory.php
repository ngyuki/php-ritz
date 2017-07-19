<?php
namespace Ritz\Bootstrap;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\FilesystemCache;

use Ritz\Middleware\RouteMiddleware;
use Ritz\Middleware\RenderMiddleware;
use Ritz\Middleware\DispatchMiddleware;
use Ritz\Router\Resolver;
use Ritz\Router\Router;
use Ritz\Dispatcher\ActionInvoker;
use Ritz\View\RendererInterface;
use Ritz\View\PhpRenderer;

class ContainerFactory
{
    /**
     * @param array $definitions
     * @return \DI\Container
     */
    public function create(array $definitions)
    {
        $definitions += $this->getDefaultDefinitions();

        $builder = new ContainerBuilder();

        if (isset($definitions['app.cache_dir']) && $definitions['app.cache_dir'] !== null) {
            $cache = new FilesystemCache($definitions['app.cache_dir']);
            $builder->setDefinitionCache($cache);
        }

        $container = $builder->addDefinitions($definitions)->build();
        return $container;
    }

    public function getDefaultDefinitions()
    {
        return [
            'debug' => true,
            'app.cache_dir' => null,

            ContainerInterface::class => function ($container) {
                return $container;
            },

            RouteMiddleware::class => function (ContainerInterface $container) {
                return new RouteMiddleware($container->get(Router::class), $container->get(Resolver::class));
            },

            DispatchMiddleware::class => function (ContainerInterface $container) {
                return new DispatchMiddleware($container, $container->get(ActionInvoker::class));
            },

            RenderMiddleware::class => function (ContainerInterface $container) {
                return new RenderMiddleware($container->get(RendererInterface::class));
            },

            Router::class => function (ContainerInterface $container) {
                return new Router($container->get('app.routes'), $container->get('app.cache_dir'));
            },

            Resolver::class => function (ContainerInterface $container) {
                return new Resolver($container);
            },

            ActionInvoker::class => function (ContainerInterface $container) {
                return new ActionInvoker($container);
            },

            RendererInterface::class => function (ContainerInterface $container) {
                return new PhpRenderer($container->get('app.view.directory'), $container->get('app.view.suffix'));
            },
        ];
    }
}
