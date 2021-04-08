<?php
namespace Ritz\Bootstrap;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ritz\RequestHandler\FinalRequestHandler;

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
    public function handle(MiddlewareInterface $app, ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new MiddlewarePipe();

        $pipeline->pipe($this->protocolVersionMiddleware());
        $pipeline->pipe($this->outputBufferingHandlerMiddleware());
        $pipeline->pipe($app);

        return $pipeline->process($request, new FinalRequestHandler());
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
     * @return MiddlewareInterface
     */
    private function outputBufferingHandlerMiddleware(): MiddlewareInterface
    {
        return new CallableMiddlewareDecorator(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            ob_start();
            try {
                return $handler->handle($request);
            } finally {
                ob_end_clean();
            }
        });
    }

    /**
     * リクエストのプロトコルバージョンを元にレスポンスのプロトコルバージョンを設定するミドルウェア
     *
     * @return MiddlewareInterface
     */
    private function protocolVersionMiddleware(): MiddlewareInterface
    {
        return new CallableMiddlewareDecorator(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            $response = $response->withProtocolVersion($request->getProtocolVersion());
            return $response;
        });
    }
}
