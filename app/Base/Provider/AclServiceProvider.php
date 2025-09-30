<?php
// app/Base/Provider/AclServiceProvider.php
namespace Base\Provider;

use Core\Acl\Access;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Interface\ServiceProvider;
use Core\Events\Event;
use Core\Http\Response;
use Core\Mvc\Dispatcher;

class AclServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('access', function($di) {
            $config = $di->get('config');
            $access = new Access($config['acl']);
            $eventsManager = $di->get('eventsManager');
            $eventsManager->attach('application:beforeDispatch', function(Event $event) use ($di, $access, $config) {
                /** @var Dispatcher $dispatcher */
                $dispatcher = $event->getData();
                $role       = $di->get('session')->get('role', 'guest');
                $module     = $dispatcher->getModuleName();
                $controller = $dispatcher->getControllerName();
                $action     = $dispatcher->getActionName();
                if (!$access->isAllowed($role, $module, $controller, $action)) {
                    $event->stopPropagation();
                    return $dispatcher->forward($config['acl']['denied']);
                }
                return true;
            });
            return $access;
        });
        $container->get('access');
    }
}