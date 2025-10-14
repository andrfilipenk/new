<?php
// app/User/Module.php
namespace User;

use Core\Di\Interface\Container;
use Core\Mvc\AbstractModule;

class Module extends AbstractModule
{
    public function boot(Container $di, $module, $controller, $action)
    {
        $di->get('app')
            ->addMiddleware(\Core\Mvc\Middleware\AuthMiddleware::class, ['except' => ['User.Auth.login']])
            ->addMiddleware(\Core\Mvc\Middleware\CsrfMiddleware::class, ['only' => ['User.User.*', 'User.Group.*']]);
    }
}