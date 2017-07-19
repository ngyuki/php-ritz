<?php
namespace App\Controller;

use App\Component\IdentityStab;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\RedirectResponse;
use Ritz\View\ViewModel;
use App\Service\LoginService;
use Zend\Diactoros\ServerRequestFactory;

class LoginControllerTest extends TestCase
{
    /**
     * @test
     */
    public function loginAction_ok()
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withParsedBody([
            'username' => 'ore',
            'password' => 'ore',
        ]);

        $identity = new IdentityStab();

        $controller = new LoginController();
        $response = $controller->loginAction($request, new LoginService(), $identity);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue($identity->is());

    }

    /**
     * @test
     */
    public function loginAction_error()
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withParsedBody([
            'username' => 'ore',
            'password' => 'are',
        ]);

        $identity = new IdentityStab();

        $controller = new LoginController();
        $response = $controller->loginAction($request, new LoginService(), $identity);

        self::assertInstanceOf(ViewModel::class, $response);
        self::assertEquals('App/Login/index', $response->getTemplate());
        self::assertFalse($identity->is());
    }
}
