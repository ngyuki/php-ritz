<?php
namespace Ritz\View;

use Psr\Http\Message\ServerRequestInterface;
use Ritz\Router\RouteResult;

class TemplateResolver
{
    /**
     * @var array
     */
    private $map;

    /**
     * @param array $map
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ViewModel $response
     * @return string
     */
    public function resolve(ServerRequestInterface $request, ViewModel $response)
    {
        $template = $response->getTemplate();

        if ($template === null) {
            $defaultTemplate = $this->getDefaultTemplate($request);
            return $defaultTemplate;
        } else if (preg_match('/^\.+\//', $template)) {
            $defaultTemplate = $this->getDefaultTemplate($request);
            return dirname($defaultTemplate) . '/' . $template;
        } else {
            return $template;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getDefaultTemplate(ServerRequestInterface $request)
    {
        $route = $request->getAttribute(RouteResult::class);

        if ($route === null) {
            throw new \LogicException("No default template ... route result is null");
        }

        $class = $route->getInstance();

        if ($class === null) {
            throw new \LogicException("No default template ... instance is null");
        }

        if (is_object($class)) {
            if ($class instanceof \Closure) {
                throw new \LogicException("No default template ... instance is closure");
            }
            $class = get_class($class);
        }

        if (is_string($class) === false) {
            throw new \LogicException("No default template ... instance is not string");
        }

        $class = $this->applyClassMap($class);

        $class = preg_replace('/Controller$/', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $template = $class;

        $method = $route->getMethod();

        if ($method !== null) {
            $action = preg_replace('/Action$/', '', $method);
            $template .= '/' . $action;
        }
        
        return $template;
    }

    /**
     * @param string $class
     * @return string
     */
    private function applyClassMap($class)
    {
        foreach ($this->map as $prefix => $dir) {
            if (substr($class, 0, strlen($prefix)) === $prefix) {
                return $dir . substr($class, strlen($prefix));
            }
        }
        return $class;
    }
}
