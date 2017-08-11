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
        $result = RouteResult::from($request);

        if ($result === null) {
            throw new \LogicException("No default template");
        }

        $instance = $result->getInstance();

        if ($instance === null) {
            throw new \LogicException("No default template");
        }

        $class = get_class($instance);
        $method = $result->getMethod();

        $class = $this->applyClassMap($class);

        $class = preg_replace('/Controller$/', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $action = preg_replace('/Action$/', '', $method);

        $template = "$class/$action";
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
