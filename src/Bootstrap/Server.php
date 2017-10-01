<?php
namespace Ritz\Bootstrap;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Ritz\Delegate\FinalDelegate;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Stratigility\MiddlewarePipe;

class Server
{
    /**
     * @var EmitterInterface
     */
    private $emitter;

    public function __construct(EmitterInterface $emitter = null)
    {
        if ($emitter === null) {
            $emitter = new SapiEmitter();
        }
        $this->emitter = $emitter;
    }

    public function run(MiddlewareInterface $app)
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = $this->handle($app, $request);

        $this->emitter->emit($response);
    }

    /**
     * @param MiddlewareInterface $app
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(MiddlewareInterface $app, ServerRequestInterface $request)
    {
        $pipeline = new MiddlewarePipe();

        $pipeline->pipe($this->protocolVersionMiddleware());
        $pipeline->pipe($this->outputBufferingHandlerMiddleware());
        $pipeline->pipe($app);

        return $pipeline->process($request, new FinalDelegate());
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
    private function outputBufferingHandlerMiddleware()
    {
        return function (ServerRequestInterface $request, DelegateInterface $delegate) {
            ob_start();
            try {
                $response = $delegate->process($request);
            } finally {
                ob_end_clean();
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
