<?php
namespace Ritz\Router;

use Psr\Http\Message\ServerRequestInterface;

class RouteResult
{
    /**
     * @var object
     */
    private $instance;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $template;

    /**
     * @param object $instance
     * @param string $method
     * @param string $template
     */
    public function __construct($instance, $method, $template)
    {
        $this->instance = $instance;
        $this->method = $method;
        $this->template = $template;
    }

    /**
     * @return object
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param ServerRequestInterface $request
     * @return self
     */
    public static function from(ServerRequestInterface $request)
    {
        return $request->getAttribute(self::class);
    }
}
