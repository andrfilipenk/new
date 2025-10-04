<?php
// app/User/Provider/SessionServiceProvider.php
namespace User\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Events\Event;
use Core\Session\DatabaseSession;
use Core\Session\DatabaseSessionHandler;

class SessionServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('sessionHandler', function($di) {
            $config = $di->get('config');
            $handler = new DatabaseSessionHandler($config['session']);
            $handler->setDI($di);
            return $handler;
        });

        $container->set('session', function($di) {
            $config  = $di->get('config');
            $handler = $di->get('sessionHandler');
            $session = new DatabaseSession($handler);
            if (isset($config['session']['name'])) {
                $session->setName($config['session']['name']);
            }
            $eventsManager = $di->get('eventsManager');
            $session->setDI($di);
            $session->setEventsManager($eventsManager);

            $eventsManager->attach('application:beforeResponse', function(Event $event) use ($session) {
                $session->writeClose();
            });
            return $session;
        });
    }
}