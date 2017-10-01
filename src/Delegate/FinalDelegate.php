<?php
namespace Ritz\Delegate;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\Router\RouteResult;
use Zend\Diactoros\Response;

class FinalDelegate implements DelegateInterface
{
    public function process(ServerRequestInterface $request)
    {
        $status = 404;
        $route = $request->getAttribute(RouteResult::class);
        if ($route && $route instanceof RouteResult) {
            $status = $route->getStatus();
        }
        $response = (new Response())->withStatus($status);
        $response->getBody()->write(sprintf(
            "%s %s ... %s %s",
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $request->getMethod(),
            (string)$request->getUri()
        ));
        return $response;
    }

}
