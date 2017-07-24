<?php
namespace Ritz\App\Bootstrap;

use DI\ContainerBuilder;
use Doctrine\Common\Cache\FilesystemCache;
use Ritz\Bootstrap\Configure;

class ContainerFactory
{
    /**
     * コンテナを作成する
     *
     * @return \DI\Container
     */
    public function create()
    {
        // コンフィグを読み分けるための環境変数
        $env = getenv('APP_ENV');

        // アプリケーションのコンフィグファイルのリスト
        // glob で複数のファイルを取得する
        $files = array_merge(
            glob(__DIR__ . '/../../bootstrap/*.php'),
            glob(__DIR__ . "/../../config/$env.php"),
            glob(__DIR__ . '/../../config/local.php')
        );

        // コンフィグファイルの読み込み
        // 複数のファイルの内容が Configure クラスによってマージされる
        $definitions = (new Configure())->init($files);

        // PHP-DI のコンテナビルダ
        $builder = new ContainerBuilder();

        // キャッシュが有効なら PHP-DI にキャッシュを指定する
        if (isset($definitions['app.cache_dir']) && $definitions['app.cache_dir'] !== null) {
            $cache = new FilesystemCache($definitions['app.cache_dir']);
            $builder->setDefinitionCache($cache);
        }

        // コンテナを作成する
        $container = $builder->addDefinitions($definitions)->build();
        return $container;
    }
}

