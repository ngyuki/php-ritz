<?php
namespace Ritz\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\View\RendererInterface;
use Ritz\View\ViewModel;

class RenderMiddleware implements MiddlewareInterface
{
    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            if ($response->getTemplate() === null) {
                throw new \LogicException("Missing template in ViewModel ({$request->getUri()})");
            }
            $content = $this->renderer->render($response->getTemplate(), $response->getVariables());
            $response->getBody()->write($content);
            $response->getBody()->rewind();
        }

        return $response;
    }
}
