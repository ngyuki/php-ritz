<?php
namespace Ritz\Bootstrap;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;

use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\Middleware\NotFoundHandler;

class Server
{
    public function run(MiddlewareInterface $app)
    {
        $pipeline = new MiddlewarePipe();

        $pipeline->pipe($this->protocolVersionMiddleware());
        $pipeline->pipe($this->directOutputHandlerMiddleware());

        $pipeline->pipe($app);

        $request = ServerRequestFactory::fromGlobals();

        $response = $this->handle($pipeline, $request);

        $emitter = new SapiEmitter();
        $emitter->emit($response);
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
     * 直接出力された内容を握りつぶす
     *
     * デフォだと zend-stratigility の Emitter が Content-Length を吐くため、
     * 直接出力していると Response オブジェクトのサイズと実際の出力サイズに齟齬が生じて、
     * 出力が途中で切れてしまう。
     *
     * このミドルウェアは出力バッファリングを用いて直接出力を握りつぶす。
     *
     * @return \Closure
     */
    private function directOutputHandlerMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {
            ob_start();
            try {
                $response = $delegate->process($request);
            } finally {
                ob_clean();
            }
            return $response;
        };
    }

    /**
     * リクエストのプロトコルバージョンを元にレスポンスのプロトコルバージョンを設定するミドルウェア
     *
     * @return \Closure
     */
    private function protocolVersionMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {
            $response = $delegate->process($request);
            $response = $response->withProtocolVersion($request->getProtocolVersion());
            return $response;
        };
    }
}
