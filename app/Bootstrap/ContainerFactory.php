<?php
namespace Ritz\App\Bootstrap;

use DI\ContainerBuilder;
use Doctrine\Common\Cache\FilesystemCache;
use Ritz\Bootstrap\Configure;

class ContainerFactory
{
    public function create()
    {
        $env = getenv('APP_ENV');

        $files = array_merge(
            glob(__DIR__ . '/../../bootstrap/*.php'),
            glob(__DIR__ . "/../../config/$env.php"),
            glob(__DIR__ . '/../../config/local.php')
        );

        $definitions = (new Configure())->init($files);

        $builder = new ContainerBuilder();

        if (isset($definitions['app.cache_dir']) && $definitions['app.cache_dir'] !== null) {
            $cache = new FilesystemCache($definitions['app.cache_dir']);
            $builder->setDefinitionCache($cache);
        }

        $container = $builder->addDefinitions($definitions)->build();
        return $container;
    }
}

