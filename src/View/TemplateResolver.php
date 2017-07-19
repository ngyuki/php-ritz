<?php
namespace Ritz\View;

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
     * @param string $class
     * @param string $method
     * @return string
     */
    public function resolve($class, $method)
    {
        $class = $this->renameClass($class);

        $class = preg_replace('/Controller$/', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $action = preg_replace('/Action$/', '', $method);
        return $template = "$class/$action";
    }

    /**
     * @param string $class
     * @return string
     */
    public function renameClass($class)
    {
        foreach ($this->map as $prefix => $dir) {
            if (substr($class, 0, strlen($prefix)) === $prefix) {
                return $dir . substr($class, strlen($prefix));
            }
        }
        return $class;
    }
}
