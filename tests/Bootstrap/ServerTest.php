<?php
namespace Ritz\Test\Bootstrap;

use PHPUnit\Framework\TestCase;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Response\EmitterInterface;
use Ritz\Bootstrap\Server;

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
        $app->pipe(function (ServerRequestInterface $request, DelegateInterface $delegate) {
            echo 'dummy';
            return $delegate->process($request);
        });

        $server = new Server($this->createEmitter());
        $server->run($app);

        $this->expectOutputString('');
    }
}
