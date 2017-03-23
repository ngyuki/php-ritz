<?php
namespace ngyuki\Ritz;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\NoopFinalHandler;
use Zend\Stratigility\Middleware\NotFoundHandler;

class Bootstrap
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function init(array $bootFiles)
    {
        $definitions = [];

        foreach ($bootFiles as $fn) {
            /** @noinspection PhpIncludeInspection */
            $definitions = (require $fn) + $definitions;
        }

        $this->container = (new ContainerFactory())->create($definitions);
    }

    public function run($app)
    {
        $pipeline = new MiddlewarePipe();

        if ($this->container->get('debug')) {
            $pipeline->pipe($this->dumpOutputMiddleware());
            $pipeline->pipe($this->debugErrorHandlerMiddleware());

        } else {
            $pipeline->pipe($this->errorHandlerMiddleware());
        }

        $pipeline->pipe($this->container->get($app));
        $pipeline->pipe(new NotFoundHandler(new Response()));

        $server = Server::createServerFromRequest($pipeline, ServerRequestFactory::fromGlobals());
        $server->listen(new NoopFinalHandler());
    }

    /**
     * 直接出力したらレスポンスをその内容に差し替えるミドルウェア
     *
     * デフォだと zend-stratigility の Emitter が Content-Length を吐くため、
     * 直接出力していると Response オブジェクトのサイズと実際の出力サイズに齟齬が生じて、
     * 出力が途中で切れてしまう。
     *
     * このミドルウェアでは出力バッファリングで直接出力を検出し、
     * 直接出力されていたらレスポンスを差し替える。
     *
     * デバッグ用に使用されるべき、プロダクションでこのミドルウェアは使用するべきではない。
     *
     * @return \Closure
     */
    private function dumpOutputMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {

            ob_start();
            $response = $delegate->process($request);
            $output = ob_get_clean();
            if (strlen($output)) {
                $response = new HtmlResponse($output);
                $response = $response->withStatus(500);
            }
            return $response;
        };
    }

    /**
     * 最上位のエラーハンドラミドルウェア
     *
     * すべての例外をキャッチし、エラーログへ出力し、500 レスポンスを返す。
     * 最低限の表示しか行われないので、独自の表示をしたければアプリケーションで独自のミドルウェアを用いること。
     *
     * @return \Closure
     */
    private function errorHandlerMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {
            try {
                return $delegate->process($request);
            } catch (\Exception $ex) {
                error_log($ex);
                $response = (new TextResponse(''))->withStatus(500);
                $response->getBody()->write($response->getStatusCode() . ' ' . $response->getReasonPhrase());
                return $response;
            }
        };
    }

    /**
     * 最上位のエラーハンドラミドルウェア(デバッグ版)
     *
     * @return \Closure
     */
    private function debugErrorHandlerMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {
            try {
                return $delegate->process($request);
            } catch (\Exception $ex) {
                $response = (new TextResponse((string)$ex))->withStatus(500);
                return $response;
            }
        };
    }
}
