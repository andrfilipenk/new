<?php
// app/User/Provider/AclServiceProvider.php
namespace User\Provider;

use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Interface\ServiceProvider;
use Core\Events\Event;

class AclServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        
        $container->set('access', false);
        $di = $container;
        $em = $container->get('eventsManager');
        $em->attach('dispatcher.beforeExecute', function(Event $event) use($di) {
            
            $config = $di->get('config');
            $public = $config['acl']['public'];
            foreach ($public as $row) {
                list($module, $controller, $action) = $row;

                var_dump($module);
                var_dump($controller);
                var_dump($action);
            }
            exit;
            $event->stopPropagation();
        });
        
        
    }
}