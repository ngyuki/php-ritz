<?php
namespace Ritz\Test\Bootstrap;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Interop\Container\ContainerInterface as InteropContainerInterface;
use Invoker\Exception\NotEnoughParametersException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use stdClass;
use Ritz\Dispatcher\ActionInvoker;

class ActionInvokerTest extends TestCase
{
    private function createPsrContainer(array $instances = [])
    {
        return new class($instances) extends \ArrayObject implements PsrContainerInterface {

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

    private function createInteropContainer(array $instances = [])
    {
        return new class($instances) extends \ArrayObject implements InteropContainerInterface {

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

    /**
     * インジェクションは「タイプヒント(属性) > タイプヒント(コンテナ) > パラメータ名(属性)」の順番で優先
     *
     * @test
     */
    public function prefer_attr_over_name_and_container()
    {
        $container = $this->createInteropContainer();
        $request = ServerRequestFactory::fromGlobals();
        $delegate = new CallableDelegateDecorator(function(){}, new Response());

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
        $invoker->invoke($request, $delegate, $instance, 'action');

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
        $container = $this->createInteropContainer();
        $request = ServerRequestFactory::fromGlobals();
        $delegate = new CallableDelegateDecorator(function(){}, new Response());

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
        $invoker->invoke($request, $delegate, $instance, 'action');

        assertSame($instance->args, [$container[stdClass::class]]);
    }


    /**
     * インジェクションは「タイプヒント(属性) > タイプヒント(コンテナ) > パラメータ名(属性)」の順番で優先
     *
     * @test
     */
    public function prefer_attr_over_container_in_name()
    {
        $container = $this->createInteropContainer();
        $request = ServerRequestFactory::fromGlobals();
        $delegate = new CallableDelegateDecorator(function(){}, new Response());

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
        $invoker->invoke($request, $delegate, $instance, 'action');

        assertSame($instance->args, [$request->getAttribute('xxx')]);
    }

    /**
     * コンテナからはパラメータ名では解決されない
     * @test
     */
    public function does_not_resolve_parameter_name_from_container()
    {
        $container = $this->createInteropContainer();
        $request = ServerRequestFactory::fromGlobals();
        $delegate = new CallableDelegateDecorator(function(){}, new Response());

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
        $invoker->invoke($request, $delegate, $instance, 'action');
    }

    /**
     * PSR コンテナインタフェースもサポートする
     *
     * PHP-DI/Invoker の 1.x が Interop しか対応していないけどアダプタで対応する
     *
     * @test
     */
    public function psr_container_support()
    {
        $container = $this->createPsrContainer();
        $request = ServerRequestFactory::fromGlobals();
        $delegate = new CallableDelegateDecorator(function(){}, new Response());

        $container[stdClass::class] = new stdClass();
        $request = $request->withAttribute('xxx', new stdClass());

        $instance = new class {
            public $args;
            function action(stdClass $xxx)
            {
                $this->args = func_get_args();
            }
        };

        $invoker = new ActionInvoker($container);
        $invoker->invoke($request, $delegate, $instance, 'action');
        assertSame($instance->args, [$container[stdClass::class]]);
    }
}
