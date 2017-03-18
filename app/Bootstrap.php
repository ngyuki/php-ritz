<?php
namespace App;

use ngyuki\Ritz\Bootstrap as BaseBootstrap;

class Bootstrap extends BaseBootstrap
{
    public function __construct()
    {
        $this->init(glob(__DIR__ . '/../boot/*.php'));
    }
}
