<?php
namespace Ritz\App\Bootstrap;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\FilesystemCache;
use Ritz\Bootstrap\Configure;
use Ritz\Router\Router;
use Ritz\View\RendererInterface;
use Ritz\View\PhpRenderer;
use Ritz\View\TemplateResolver;

class ContainerFactory
{
    public function create()
    {
        $env = getenv('APP_ENV');

        $files = array_merge(
            glob(__DIR__ . '/../../boot/*.php'),
            glob(__DIR__ . "/../../config/$env.php"),
            glob(__DIR__ . '/../../config/local.php')
        );

        $definitions = (new Configure())->init($files);

        $definitions += $this->getDefault();

        $builder = new ContainerBuilder();

        if (isset($definitions['app.cache_dir']) && $definitions['app.cache_dir'] !== null) {
            $cache = new FilesystemCache($definitions['app.cache_dir']);
            $builder->setDefinitionCache($cache);
        }

        $container = $builder->addDefinitions($definitions)->build();
        return $container;
    }

    protected function getDefault()
    {
        return [
            'debug' => true,
            'app.cache_dir' => null,

            ContainerInterface::class => function ($container) {
                return $container;
            },

            Router::class => function (ContainerInterface $container) {
                return new Router($container->get('app.routes'), $container->get('app.cache_dir'));
            },

            RendererInterface::class => function (ContainerInterface $container) {
                return new PhpRenderer($container->get('app.view.directory'), $container->get('app.view.suffix'));
            },

            TemplateResolver::class => function (ContainerInterface $container) {
                return new TemplateResolver($container->get('app.view.autoload'));
            },
        ];
    }
}

