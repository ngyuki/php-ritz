<?php
namespace Ritz\Test\Bootstrap;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\MiddlewarePipe;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ritz\Bootstrap\Server;
use function PHPUnit\Framework\assertEquals;

class ServerTest extends TestCase
{
    /**
     * @var ResponseInterface
     */
    private $response;

    private function createEmitter()
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->method('emit')->willReturnCallback(function ($response) {
            $this->response = $response;
            return true;
        });
        return $emitter;
    }

    /**
     * @test
     */
    function no_change_ob_level()
    {
        $app = new MiddlewarePipe();

        $level = ob_get_length();

        $server = new Server($this->createEmitter());
        $server->run($app);

        assertEquals($level, ob_get_length());
    }

    /**
     * @test
     */
    function not_fond()
    {
        $app = new MiddlewarePipe();

        $server = new Server($this->createEmitter());
        $server->run($app);

        assertEquals(404, $this->response->getStatusCode());
    }

    /**
     * @test
     */
    function must_empty_output()
    {
        $app = new MiddlewarePipe();
        $app->pipe(new CallableMiddlewareDecorator(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            echo 'dummy';
            return $handler->handle($request);
        }));

        $server = new Server($this->createEmitter());
        $server->run($app);

        assertEquals('', $this->response->getBody()->getContents());

        $this->expectOutputString('');
    }
}
