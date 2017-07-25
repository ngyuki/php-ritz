<?php
namespace Ritz\App\Bootstrap;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewarePipe;
use Ritz\Middleware\DispatchMiddleware;
use Ritz\Middleware\RenderMiddleware;
use Ritz\Middleware\RouteMiddleware;
use Ritz\App\Middleware\ErrorMiddleware;
use Ritz\App\Middleware\LoginMiddleware;
use Franzl\Middleware\Whoops\PSR15Middleware as WhoopsMiddleware;

/**
 * アプリケーションクラス
 */
class Application implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $pipeline = new MiddlewarePipe();

        if ($this->container->get('debug')) {
            // デバッグ時は Whoops を有効にする
            // このミドルウェアはすべての例外をキャッチしてデバッグ用ページをレスポンスに書き込む
            $pipeline->pipe(new WhoopsMiddleware());
        }

        // ルーティング結果をリクエストの属性に設定する
        $pipeline->pipe($this->container->get(RouteMiddleware::class));

        // コントローラーやミドルウェアが返した ViewModel を元にテンプレートをレンダリングする
        $pipeline->pipe($this->container->get(RenderMiddleware::class));

        // 例外発生時にエラーページのテンプレートを選択するミドルウェア
        // すべての例外をキャッチしてエラーページのための ViewModel を返す
        $pipeline->pipe($this->container->get(ErrorMiddleware::class));

        if ($this->container->get('debug')) {

            // デバッグ時は Whoops を有効にする
            // このミドルウェアはすべての例外をキャッチしてデバッグ用ページをレスポンスに書き込む
            $pipeline->pipe(new WhoopsMiddleware());
        }

        // ログインをチェックしてログイン画面へのリダイレクトを行うミドルウェア
        $pipeline->pipe($this->container->get(LoginMiddleware::class));

        // ルーティング結果を元にコントローラーのアクションメソッドをディスパッチする
        $pipeline->pipe($this->container->get(DispatchMiddleware::class));

        return $pipeline->process($request, $delegate);
    }
}
