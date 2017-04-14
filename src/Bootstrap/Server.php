<?php
namespace ngyuki\Ritz\Bootstrap;

use ngyuki\Ritz\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\Response\SapiEmitter;

use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\Middleware\NotFoundHandler;

class Server
{
    public function run(MiddlewareInterface $app, $debug)
    {
        $pipeline = new MiddlewarePipe();

        if ($debug) {
            $pipeline->pipe($this->dumpOutputMiddleware());
        } else {
            $pipeline->pipe($this->errorHandlerMiddleware());
        }

        $pipeline->pipe($app);

        $request = ServerRequestFactory::fromGlobals();

        ob_start();
        $level = ob_get_level();

        $response = $this->handle($pipeline, $request);

        $emitter = new SapiEmitter();
        $emitter->emit($response, $level);
    }

    /**
     * @param MiddlewareInterface $app
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(MiddlewareInterface $app, ServerRequestInterface $request)
    {
        return $app->process($request, new CallableDelegateDecorator(
            function (ServerRequestInterface $request, ResponseInterface $response) {
                return (new NotFoundHandler($response))($request, $response, function () {});
            },
            new Response()
        ));
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
            } catch (HttpException $ex) {
                return new TextResponse($ex->getCode() . ' ' . $ex->getMessage(), $ex->getCode());
            } catch (\Exception $ex) {
                error_log($ex);
                return new TextResponse('Unexpected Error', 500);
            }
        };
    }
}
