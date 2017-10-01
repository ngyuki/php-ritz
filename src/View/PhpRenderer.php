<?php
namespace Ritz\View;

class PhpRenderer implements RendererInterface
{
    /**
     * @var string|null
     */
    private $layout = null;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $suffix;

    public function __construct($directory, $suffix = '.phtml')
    {
        $this->directory = $directory;
        $this->suffix = $suffix;
    }

    public function render($template, array $variables)
    {
        $content = "";

        for (;;) {
            $content = (function(){
                ob_start();
                try {
                    $params = func_get_arg(1);
                    extract($params);
                    /** @noinspection PhpIncludeInspection */
                    include func_get_arg(0);
                    return ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            })($this->resolve($template), $variables);

            if ($this->layout === null) {
                break;
            }

            $template = $this->layout;
            $variables['content'] = $content;
            $this->disableLayout();
        }

        return $content;
    }

    /**
     * @param string $template
     * @return string
     */
    protected function resolve($template)
    {
        return $this->directory . DIRECTORY_SEPARATOR . $template . $this->suffix;
    }

    /**
     * @param string $name
     */
    public function layout(string $name)
    {
        $this->layout = $name;
    }

    public function disableLayout()
    {
        $this->layout = null;
    }

    public function h(string $str): string
    {
        $charset = ini_get('default_charset') ?: 'UTF-8';
        return htmlspecialchars($str, ENT_QUOTES, $charset);
    }
}
