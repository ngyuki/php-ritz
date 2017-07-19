<?php
namespace Ritz\App\Service;

class HelloService
{
    /**
     * @var string
     */
    private $hello;

    public function __construct(string $hello)
    {
        $this->hello = $hello;
    }

    public function say($name = "World")
    {
        return "$this->hello $name!";
    }
}
