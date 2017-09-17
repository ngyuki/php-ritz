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
    public function route($method, $uri)
    {
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if ($routeInfo[0] != Dispatcher::FOUND) {
            return null;
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

        return [$handler, $params];
    }
}
