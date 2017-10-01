<?php
namespace Ritz\Router;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\Dispatcher;

class FastRouter implements RouterInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(callable $callback, $cacheFile)
    {
        $dispatchData = $this->loadCache($cacheFile);

        if ($dispatchData === null) {
            $routeCollector = new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator);
            $callback($routeCollector);
            $dispatchData = $routeCollector->getData();
            $this->saveCache($cacheFile, $dispatchData);
        }
        $this->dispatcher = new GroupCountBasedDispatcher($dispatchData);
    }

    private function loadCache($cacheFile)
    {
        if ($cacheFile === null) {
            return null;
        }

        if (file_exists($cacheFile) === false) {
            return null;
        }

        /** @noinspection PhpIncludeInspection */
        $dispatchData = require $cacheFile;
        if (!is_array($dispatchData)) {
            trigger_error("Invalid cache file \"$dispatchData\"");
            return null;
        }
        return $dispatchData;
    }

    private function saveCache($cacheFile, array $dispatchData)
    {
        if ($cacheFile === null) {
            return null;
        }

        $dir = dirname($cacheFile);
        if (is_dir($dir) === false) {
            mkdir($dir, 0777&~umask(), true);
        }

        file_put_contents($cacheFile, '<?php return ' . var_export($dispatchData, true) . ';');
    }

    /**
     * {@inheritdoc}
     */
    public function route($httpMethod, $uri)
    {
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        if ($routeInfo[0] == Dispatcher::NOT_FOUND) {
            return new RouteResult(404, null, null, []);
        }

        if ($routeInfo[0] == Dispatcher::METHOD_NOT_ALLOWED) {
            return new RouteResult(405, null, null, []);
        }

        $handler = [];
        $params = $routeInfo[2];

        foreach ($routeInfo[1] as $name => $value) {
            if (is_string($name)) {
                $params[$name] = $value;
            } else {
                $handler[$name] = $value;
            }
        }

        if (count($handler) === 0) {
            return new RouteResult(500, null, null, []);
        }

        $handler = array_values($handler);
        $instance = $handler[0];
        $method = null;

        if (count($handler) > 1) {
            $method = $handler[1];
        }

        $result = new RouteResult(200, $instance, $method, $params);
        return $result;
    }
}
