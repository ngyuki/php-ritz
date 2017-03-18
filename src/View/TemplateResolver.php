<?php
namespace ngyuki\Ritz\View;

class TemplateResolver
{
    private $directory;
    private $suffix;

    public function __construct($directory, $suffix = '.phtml')
    {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->suffix = $suffix;
    }

    public function resolve($template)
    {
        return $this->directory . DIRECTORY_SEPARATOR . $template . $this->suffix;
    }
}
