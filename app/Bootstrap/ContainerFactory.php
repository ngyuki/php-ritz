<?php
namespace App\Bootstrap;

use ngyuki\Ritz\Bootstrap\Configure;
use ngyuki\Ritz\Bootstrap\ContainerFactory as BaseContainerFactory;

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

        $config = (new Configure())->init($files);
        $container = (new BaseContainerFactory())->create($config);
        return $container;
    }
}
