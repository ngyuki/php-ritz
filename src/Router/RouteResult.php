<?php
namespace Ritz\Router;

class RouteResult
{
    /**
     * @var int
     */
    private $status;

    /**
     * @var object|null
     */
    private $instance;

    /**
     * @var string|null
     */
    private $method;

    /**
     * @var array
     */
    private $params;

    /**
     * @param int $status
     * @param object|null $instance
     * @param string|null $method
     * @param array $params
     */
    public function __construct($status, $instance, $method, array $params)
    {
        $this->status = $status;
        $this->instance = $instance;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
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
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
