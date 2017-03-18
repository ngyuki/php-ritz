<?php
namespace App;

require __DIR__  . '/../vendor/autoload.php';

$boot = new Bootstrap();
$boot->run(Application::class);
