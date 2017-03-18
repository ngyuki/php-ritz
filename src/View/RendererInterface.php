<?php
namespace ngyuki\Ritz\View;

interface RendererInterface
{
    public function render($template, array $variables);
}
