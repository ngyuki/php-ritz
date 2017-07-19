<?php
namespace App\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Ritz\Exception\HttpException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\View\ViewModel;

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
            if ($ex instanceof HttpException === false) {
                $ex = new HttpException();
            }
            $response = (new ViewModel())
                ->withTemplate('Error/error')
                ->withVariable('message', $ex->getMessage())
                ->withStatus($ex->getCode())
            ;
            return $response;
        }
    }
}
