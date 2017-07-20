<?php
namespace Ritz\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ritz\Router\RouteResult;
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
            if ($response->getTemplate() === null) {
                $template = $this->detectTemplate($request);
                $response = $response->withTemplate($template);
            } else if (preg_match('/^\.+\//', $response->getTemplate())) {
                $template = $this->detectTemplate($request);
                $template = dirname($template) . '/' . $response->getTemplate();
                $response = $response->withTemplate($template);
            }
            $content = $this->renderer->render($response->getTemplate(), $response->getVariables());
            $response->getBody()->write($content);
            $response->getBody()->rewind();
        }

        return $response;
    }

    private function detectTemplate(ServerRequestInterface $request)
    {
        $result = RouteResult::from($request);
        if ($result === null) {
            $url = $request->getUri();
            throw new \LogicException("No default template ... RouteResult is null ($url)");
        }

        $instance = $result->getInstance();

        if ($instance === null) {
            $url = $request->getUri();
            throw new \LogicException("No default template ... Controller is null ($url)");
        }

        $template = $this->resolver->resolve(get_class($instance), $result->getMethod());
        return $template;

    }
}
