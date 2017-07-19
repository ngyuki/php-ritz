<?php
namespace Ritz\App;

return [
    //'app.cache_dir' => __DIR__ . '/../cache/',
    'app.view.directory' => dirname(__DIR__) . '/resource/view/',
    'app.view.suffix' => '.phtml',
    'app.view.autoload' => [
        'Ritz\\App\\Controller\\' => 'App/',
    ],
];
