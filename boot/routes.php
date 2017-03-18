<?php
namespace App;

use function DI\value;
use FastRoute\RouteCollector;
use App\Controller\HomeController;
use App\Controller\LoginController;

return [
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/',         [HomeController::class, 'index']);
        $r->get('/view',     [HomeController::class, 'view']);
        $r->get('/response', [HomeController::class, 'response']);
        $r->get('/raise',    [HomeController::class, 'raise']);
        $r->get('/login',    [LoginController::class, 'index']);
        $r->post('/login',   [LoginController::class, 'login']);
        $r->get('/logout',   [LoginController::class, 'logout']);

        $r->addGroup('/user', function (RouteCollector $r) {
            $r->get('/{name}', [HomeController::class, 'user']);
        });
    }),
];
