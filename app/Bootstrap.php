<?php
namespace App;

use ngyuki\Ritz\Bootstrap\Configure;
use ngyuki\Ritz\Bootstrap\ContainerFactory;
use ngyuki\Ritz\Bootstrap\Server;

class Bootstrap
{
    public static function init()
    {
        $env = getenv('APP_ENV');

        $files = array_merge(
            glob(__DIR__ . '/../boot/*.php'),
            glob(__DIR__ . "/../config/$env.php"),
            glob(__DIR__ . '/../config/local.php')
        );

        $config = (new Configure())->init($files);
        $container = (new ContainerFactory())->create($config);
        return $container;
    }

    public static function main()
    {
        $container = self::init();
        $server = new Server();
        $server->run($container->get(Application::class), $container->get('debug'));
    }
}
