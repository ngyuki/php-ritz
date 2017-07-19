<?php
namespace Ritz\Router;

use function FastRoute\simpleDispatcher;
use function FastRoute\cachedDispatcher;
use FastRoute\Dispatcher;

class Router
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(callable $callback, $cacheDir)
    {
        if ($cacheDir !== null) {
            $cacheFile = "$cacheDir/routes.php";
            $this->dispatcher = cachedDispatcher($callback, ['cacheFile' => $cacheFile]);
        } else {
            $this->dispatcher = simpleDispatcher($callback);
        }
    }

    public function route($method, $uri)
    {
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            //case Dispatcher::NOT_FOUND:
            //    throw new \RuntimeException("404 Not Found", 404);

            //case Dispatcher::METHOD_NOT_ALLOWED:
            //    throw new \RuntimeException("405 Method Not Allowed", 405);

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $params = $routeInfo[2];
                return [$handler, $params];
        }

        return [null, null];
    }
}


