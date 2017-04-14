<?php
namespace App\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use ngyuki\Ritz\Router\RouteResult;
use ngyuki\Ritz\View\ViewModel;
use App\Component\IdentityInterface;
use App\Controller\LoginController;

class LoginMiddleware implements MiddlewareInterface
{
    /**
     * @var IdentityInterface
     */
    private $identity;

    public function __construct(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $instance = RouteResult::from($request)->getInstance();

        if ($instance instanceof LoginController) {
            return $delegate->process($request);
        }

        if ($this->identity->is() === false) {
            return new RedirectResponse('/login');
        }

        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            $response = $response->withVariable('identify', $this->identity->get());
        }

        return $response;
    }
}
