<?php
namespace Ritz\App;

return [
    'debug' => true,

    // キャッシュディレクトリ
    // null ならキャッシュは使用されない
    'app.cache_dir' => null,

    // テンプレートファイルを格納するディレクトリ
    'app.view.directory' => dirname(__DIR__) . '/resource/view/',

    // テンプレートファイルの拡張子
    'app.view.suffix' => '.phtml',

    // コントローラーのクラス名からテンプレート名へのマッピング
    'app.view.autoload' => [
        'Ritz\\App\\Controller\\' => 'App/',
    ],
];
