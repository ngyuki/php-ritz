<?php
namespace Ritz\App;

use function DI\object;
use function DI\get;
use function DI\value;

use Psr\Container\ContainerInterface;
use Ritz\Router\Router;
use Ritz\View\PhpRenderer;
use Ritz\View\RendererInterface;


use Ritz\App\Service\HelloService;
use Ritz\App\Component\Identity;
use Ritz\App\Component\IdentityInterface;
use Ritz\View\TemplateResolver;

return [
    Router::class => function (ContainerInterface $container) {
        return new Router($container->get('app.routes'), $container->get('app.cache_dir'));
    },

    RendererInterface::class => function (ContainerInterface $container) {
        return new PhpRenderer($container->get('app.view.directory'), $container->get('app.view.suffix'));
    },

    TemplateResolver::class => function (ContainerInterface $container) {
        return new TemplateResolver($container->get('app.view.autoload'));
    },

    HelloService::class => object(HelloService::class)->constructor(get('hello')),
    'hello' => value("Hello"),

    IdentityInterface::class => get(Identity::class),
];
