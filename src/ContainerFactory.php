<?php
namespace ngyuki\Ritz;

use Psr\Container\ContainerInterface;

use ngyuki\Ritz\Router\Router;
use ngyuki\Ritz\View\RendererInterface;
use ngyuki\Ritz\View\PhpRenderer;
use ngyuki\Ritz\View\TemplateResolver;

use Doctrine\Common\Cache\FilesystemCache;
use DI\ContainerBuilder;
use function DI\object;
use function DI\get;

class ContainerFactory
{
    public function create(array $definitions)
    {
        $definitions += [
            'debug'                   => true,
            'app.cache_dir'           => null,
            ContainerInterface::class => function ($container) { return $container; },
            Router::class             => object(Router::class)->constructor(get('app.routes'), get('app.cache_dir')),
            TemplateResolver::class   => object(TemplateResolver::class)->constructor(get('app.view.directory'), get('app.view.suffix')),
            RendererInterface::class  => get(PhpRenderer::class),
        ];

        $builder = new ContainerBuilder();

        if (isset($definitions['app.cache_dir']) && $definitions['app.cache_dir'] !== null) {
            $cache = new FilesystemCache($definitions['app.cache_dir']);
            $builder->setDefinitionCache($cache);
        }

        $container = $builder->addDefinitions($definitions)->build();
        return $container;
    }
}
