<?php
namespace ngyuki\Ritz\Middleware;

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
     * @param object $instance
     * @param string $method
     */
    public function __construct($instance, $method)
    {
        $this->instance = $instance;
        $this->method = $method;
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
     * @param ServerRequestInterface $request
     * @return self
     */
    public static function from(ServerRequestInterface $request)
    {
        return $request->getAttribute(self::class);
    }
}
