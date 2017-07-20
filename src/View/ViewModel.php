<?php
namespace Ritz\View;

use Zend\Diactoros\Response\HtmlResponse;

class ViewModel extends HtmlResponse
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var array
     */
    private $variables = [];

    public function __construct(array $variables = [])
    {
        parent::__construct('');

        $this->variables = $variables;
    }

    public function withTemplate($template)
    {
        $new = clone $this;
        $new->template = $template;
        return $new;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function withVariables(array $variables)
    {
        $new = clone $this;
        $new->variables = $variables;
        return $new;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function withVariable($name, $value)
    {
        $new = clone $this;
        $new->variables[$name] = $value;
        return $new;
    }
}
