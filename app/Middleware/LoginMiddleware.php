<?php
namespace Ritz\App\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Ritz\Router\RouteResult;
use Ritz\View\ViewModel;
use Ritz\App\Component\IdentityInterface;
use Ritz\App\Controller\LoginController;

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
        $result = RouteResult::from($request);

        if ($result === null) {
            return $delegate->process($request);
        }

        $instance = $result->getInstance();

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
