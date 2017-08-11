<?php
namespace Ritz\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\View\RendererInterface;
use Ritz\View\TemplateResolver;
use Ritz\View\ViewModel;

class RenderMiddleware implements MiddlewareInterface
{
    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var TemplateResolver
     */
    private $resolver;

    public function __construct(RendererInterface $renderer, TemplateResolver $resolver)
    {
        $this->renderer = $renderer;
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            $template = $this->resolver->resolve($request, $response);
            $content = $this->renderer->render($template, $response->getVariables());
            $response->getBody()->write($content);
            $response->getBody()->rewind();
        }

        return $response;
    }
}
