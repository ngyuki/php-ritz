<?php
namespace Ritz\App;

use function DI\value;
use FastRoute\RouteCollector;
use Ritz\App\Controller\HomeController;
use Ritz\App\Controller\LoginController;

return [
    // ルートの定義
    'app.routes' => value(function(RouteCollector $r) {

        $r->get('/',          [HomeController::class, 'indexAction']);
        $r->get('/view',      [HomeController::class, 'viewAction']);
        $r->get('/response',  [HomeController::class, 'responseAction']);
        $r->get('/raise',     [HomeController::class, 'raiseAction']);
        $r->get('/relative',  [HomeController::class, 'relativeAction']);
        $r->get('/error/403', [HomeController::class, 'forbiddenAction']);

        $r->get('/login',    [LoginController::class, 'indexAction']);
        $r->post('/login',   [LoginController::class, 'loginAction']);
        $r->get('/logout',   [LoginController::class, 'logoutAction']);

        $r->addGroup('/user', function (RouteCollector $r) {
            $r->get('/{name}', [HomeController::class, 'userAction']);
        });
    }),
];
