<?php
return [
    'file_list' => [],

    'exclude_file_regex' => '@^vendor/.*/(tests|Tests|test|Test)/@',

    'exclude_file_list' => [],

    'directory_list' => [
        'src/',
        'vendor/container-interop/container-interop/',
        'vendor/http-interop/http-middleware/',
        'vendor/nikic/fast-route/',
        'vendor/php-di/invoker/',
        'vendor/psr/container/',
        'vendor/psr/http-message/',
        'vendor/zendframework/zend-diactoros/',
        'vendor/zendframework/zend-stratigility/',
    ],

    "exclude_analysis_directory_list" => [
        'vendor/'
    ],
];
