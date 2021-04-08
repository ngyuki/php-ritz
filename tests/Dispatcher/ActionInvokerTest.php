<?php
namespace Ritz\Test\Dispatcher;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Invoker\Exception\NotEnoughParametersException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use Ritz\Dispatcher\ActionInvoker;
use function PHPUnit\Framework\assertSame;

class ActionInvokerTest extends TestCase
{
    private function createContainer(array $instances = []): ContainerInterface
    {
        return new class($instances) extends \ArrayObject implements ContainerInterface {

            public function get($id)
            {
                return $this[$id];
            }

            public function has($id)
            {
                return isset($this[$id]);
            }
        };
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }

    /**
     * インジェクションは「タイプヒント(属性) > タイプヒント(コンテナ) > パラメータ名(属性)」の順番で優先
     *
     * @test
     */
    public function prefer_attr_over_name_and_container()
    {
        $container = $this->createContainer();
        $request = ServerRequestFactory::fromGlobals();
        $handler = $this->createRequestHandler();

        $container[stdClass::class] = new stdClass();
        $container['xxx'] = new stdClass();

        $request = $request->withAttribute(stdClass::class, new stdClass());
        $request = $request->withAttribute('xxx', new stdClass());

        $instance = new class {
            public $args;
            function action(stdClass $xxx)
            {
                $this->args = func_get_args();
            }
        };

        $invoker = new ActionInvoker($container);
        $invoker->invoke($request, $handler, $instance, 'action');

        /* @var $request ServerRequestInterface */
        assertSame($instance->args, [$request->getAttribute(stdClass::class)]);
    }

    /**
     * インジェクションは「タイプヒント(属性) > タイプヒント(コンテナ) > パラメータ名(属性)」の順番で優先
     *
     * @test
     */
    public function prefer_container_over_name()
    {
        $container = $this->createContainer();
        $request = ServerRequestFactory::fromGlobals();
        $handler = $this->createRequestHandler();

        $container[stdClass::class] = new stdClass();
        $container['xxx'] = new stdClass();

        //$request = $request->withAttribute(stdClass::class, new stdClass());
        $request = $request->withAttribute('xxx', new stdClass());

        $instance = new class {
            public $args;
            function action(stdClass $xxx)
            {
                $this->args = func_get_args();
            }
        };

        $invoker = new ActionInvoker($container);
        $invoker->invoke($request, $handler, $instance, 'action');

        assertSame($instance->args, [$container[stdClass::class]]);
    }


    /**
     * インジェクションは「タイプヒント(属性) > タイプヒント(コンテナ) > パラメータ名(属性)」の順番で優先
     *
     * @test
     */
    public function prefer_attr_over_container_in_name()
    {
        $container = $this->createContainer();
        $request = ServerRequestFactory::fromGlobals();
        $handler = $this->createRequestHandler();

        //$container[stdClass::class] = new stdClass();
        $container['xxx'] = new stdClass();

        //$request = $request->withAttribute(stdClass::class, new stdClass());
        $request = $request->withAttribute('xxx', new stdClass());

        $instance = new class {
            public $args;
            function action(stdClass $xxx)
            {
                $this->args = func_get_args();
            }
        };

        $invoker = new ActionInvoker($container);
        $invoker->invoke($request, $handler, $instance, 'action');

        assertSame($instance->args, [$request->getAttribute('xxx')]);
    }

    /**
     * コンテナからはパラメータ名では解決されない
     * @test
     */
    public function does_not_resolve_parameter_name_from_container()
    {
        $container = $this->createContainer();
        $request = ServerRequestFactory::fromGlobals();
        $handler = $this->createRequestHandler();

        //$container[stdClass::class] = new stdClass();
        $container['xxx'] = new stdClass();

        //$request = $request->withAttribute(stdClass::class, new stdClass());
        //$request = $request->withAttribute('xxx', new stdClass());

        $instance = new class {
            public $args;
            function action(stdClass $xxx)
            {
                $this->args = func_get_args();
            }
        };

        $this->expectException(NotEnoughParametersException::class);

        $invoker = new ActionInvoker($container);
        $invoker->invoke($request, $handler, $instance, 'action');
    }
}
