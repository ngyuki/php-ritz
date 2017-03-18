<?php
namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use ngyuki\Ritz\View\ViewModel;
use App\Component\Identity;
use App\Service\LoginService;

class LoginController
{
    public function indexAction()
    {
        return [];
    }

    public function loginAction(ServerRequestInterface $request, LoginService $loginService, Identity $identity)
    {
        $values = $request->getParsedBody();
        $username = $values['username'];
        $password = $values['password'];

        if ($loginService->login($username, $password)) {
            $identity->set([
                'username' => $username,
            ]);
            return new RedirectResponse('/');
        }

        return (new ViewModel())->withTemplate('App/Login/index')->withVariables([
            'username' => $username,
            'errors' => ["ユーザー名またはパスワードが異なります"],
        ]);
    }

    public function logoutAction(Identity $identity)
    {
        $identity->clear();

        return new RedirectResponse('/login');
    }
}
