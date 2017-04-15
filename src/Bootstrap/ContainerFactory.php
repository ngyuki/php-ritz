<?php
namespace ngyuki\Ritz\Bootstrap;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\FilesystemCache;

use ngyuki\Ritz\Middleware\RouteMiddleware;
use ngyuki\Ritz\Middleware\RenderMiddleware;
use ngyuki\Ritz\Middleware\DispatchMiddleware;
use ngyuki\Ritz\Router\Resolver;
use ngyuki\Ritz\Router\Router;
use ngyuki\Ritz\Dispatcher\ActionInvoker;
use ngyuki\Ritz\View\RendererInterface;
use ngyuki\Ritz\View\PhpRenderer;
use ngyuki\Ritz\View\TemplateResolver;

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
                return new PhpRenderer($container->get(TemplateResolver::class));
            },

            TemplateResolver::class => function (ContainerInterface $container) {
                return new TemplateResolver($container->get('app.view.directory'), $container->get('app.view.suffix'));
            },
        ];
    }
}
