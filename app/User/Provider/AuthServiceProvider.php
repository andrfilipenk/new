<?php
// app/User/Provider/AuthServiceProvider.php
namespace User\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class AuthServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('auth', function($di) {
            $auth = new \User\Model\Auth;
            $auth->setDI($di);
            return $auth;
        });
    }
}