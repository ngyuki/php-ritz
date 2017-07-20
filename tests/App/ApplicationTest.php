<?php
namespace Ritz\Test\App;

use Ritz\App\Bootstrap\Application;
use Ritz\App\Bootstrap\ContainerFactory;
use Ritz\App\Component\IdentityInterface;
use Ritz\App\Component\IdentityStab;
use Interop\Container\ContainerInterface;
use Ritz\Bootstrap\Server;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;
use Zend\Dom\Document;
use Zend\Dom\Document\Query;

class ApplicationTest extends TestCase
{
    function initWithIdentity()
    {
        $identity = new IdentityStab();
        $identity->set(['username' => 'oreore']);

        $container = (new ContainerFactory)->create();
        $container->set(IdentityInterface::class, $identity);

        return $container;
    }

    function createRequest($uri)
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withUri(new Uri($uri));
        return $request;
    }

    function query(Document $document, $expr)
    {
        return $this->queryAll($document, $expr)[0];
    }

    function queryAll(Document $document, $expr)
    {
        return Query::execute($expr, $document, Query::TYPE_CSS);
    }

    function handle(ServerRequestInterface $request, ContainerInterface $container = null)
    {
        $container = $container ?: (new ContainerFactory)->create();
        $response = (new Server())->handle($container->get(Application::class), $request);
        return $response;
    }

    function test_redirect_login()
    {
        $request = $this->createRequest('http://localhost/');
        $response = $this->handle($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/login', $response->getHeaderLine('Location'));
    }

    function test_top()
    {
        $container = $this->initWithIdentity();

        $request = $this->createRequest('http://localhost/');
        $response = $this->handle($request, $container);

        self::assertEquals(200, $response->getStatusCode());

    }

    function test_relativeTemplate()
    {
        $container = $this->initWithIdentity();

        $request = $this->createRequest('http://localhost/relative');
        $response = $this->handle($request, $container);

        self::assertEquals(200, $response->getStatusCode());

        $document = new Document($response->getBody()->getContents());
        self::assertContains('relative-template.phtml', $this->query($document, '#file')->textContent);
    }

    function test_notfound()
    {
        $container = $this->initWithIdentity();

        $request = $this->createRequest('http://localhost/notfound');
        $response = $this->handle($request, $container);

        self::assertEquals(404, $response->getStatusCode());
    }
}
