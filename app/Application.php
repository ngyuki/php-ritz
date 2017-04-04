<?php
namespace App;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewarePipe;

use ngyuki\Ritz\Middleware\DispatchMiddleware;
use ngyuki\Ritz\Middleware\RenderMiddleware;
use ngyuki\Ritz\Middleware\RouteMiddleware;
use ngyuki\Ritz\Middleware\RouteResult;
use ngyuki\Ritz\View\ViewModel;

use App\Component\IdentityInterface;
use App\Controller\LoginController;

class Application implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var IdentityInterface
     */
    private $identity;

    public function __construct(ContainerInterface $container, IdentityInterface $identity)
    {
        $this->container = $container;
        $this->identity = $identity;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $pipeline = new MiddlewarePipe();

        $pipeline->pipe($this->container->get(RenderMiddleware::class));

        $pipeline->pipe([$this, 'handleError']);

        $pipeline->pipe($this->container->get(RouteMiddleware::class));

        $pipeline->pipe([$this, 'checkLogin']);

        $pipeline->pipe($this->container->get(DispatchMiddleware::class));

        return $pipeline->process($request, $delegate);
    }

    public function checkLogin(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (RouteResult::from($request)->getInstance() instanceof LoginController) {
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

    public function handleError(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            return $delegate->process($request);
        } catch (\Exception $ex) {
            return (new ViewModel())->withTemplate('Error/error')->withVariable('exception', $ex);
        }
    }
}
