<?php
namespace Ritz\App\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\View\ViewModel;
use Zend\Diactoros\Response;

class ErrorMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            return $delegate->process($request);
        } catch (\Exception $ex) {
            $message = (new Response())->withStatus(500)->getReasonPhrase();
            return (new ViewModel())
                ->withVariable('message', $message)
                ->withTemplate('Error/error')
                ->withStatus(500);
        }
    }
}
