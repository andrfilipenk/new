<?php
// app/User/Module.php
namespace User;

use Core\Di\Interface\Container;
use Core\Mvc\AbstractModule;

class Module extends AbstractModule 
{
    public function boot(Container $di, $module, $controller, $action) {
        $em = $this->getDI()->get('eventsManager');
        $em->attach('dispatcher.beforeExecute', function($event) use($di, $module, $controller, $action) {
            $config = $di->get('config');
            $isPublicResource = false;
            foreach ($config['acl']['public'] as $row) {
                list($m, $c, $a) = $row;
                if ($module === $m && $controller === $c && $action === $a) {
                    $isPublicResource = true;
                    break;
                }
            }

            if (!$isPublicResource) {
                // check permissions
            }

            if (!$di->has('auth')) {
                $dispatcher = $event->getData();
                $dispatcher->setModule('User')
                    ->setController('Auth')
                    ->setAction('login');
                return $dispatcher;
            }
            /** @var \User\Model\User $user */
            #$user = $di->get('auth');
        });
    }
}