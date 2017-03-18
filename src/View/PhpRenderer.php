<?php
namespace ngyuki\Ritz\View;

class PhpRenderer implements RendererInterface
{
    /**
     * @var string
     */
    private $layout = null;

    /**
     * @var TemplateResolver
     */
    private $resolver;

    public function __construct(TemplateResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function render($template, array $variables)
    {
        $content = "";

        for (;;) {
            $content = (function(){
                ob_start();
                extract(func_get_arg(1));
                /** @noinspection PhpIncludeInspection */
                include func_get_arg(0);
                return ob_get_clean();
            })($this->resolver->resolve($template), $variables);

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
