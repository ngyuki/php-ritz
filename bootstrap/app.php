<?php
namespace Ritz\App;

return [
    'debug' => true,
    'app.cache_dir' => null,
    'app.view.directory' => dirname(__DIR__) . '/resource/view/',
    'app.view.suffix' => '.phtml',
    'app.view.autoload' => [
        'Ritz\\App\\Controller\\' => 'App/',
    ],
];
