<?php
namespace App;

use ngyuki\Ritz\Bootstrap\Configure;
use ngyuki\Ritz\Bootstrap\ContainerFactory;
use ngyuki\Ritz\Bootstrap\Server;

class Bootstrap
{
    public static function init()
    {
        $config = (new Configure())->init(glob(__DIR__ . '/../boot/*.php'));
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
