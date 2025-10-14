<?php
// app/User/Provider/AuthServiceProvider.php
namespace User\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use User\Model\User as UserModel;

class AuthServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        /** @var \Core\Session\DatabaseSession $session */
        $session = $container->get('session');
        if ($session->has('user')) {
            $id = $session->get('user');
            if ($user = UserModel::find($id)) {
                $container->set('auth', $user);
            }
        }
    }
}