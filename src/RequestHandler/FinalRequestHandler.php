<?php
namespace Ritz\RequestHandler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ritz\Router\RouteResult;

class FinalRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
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
