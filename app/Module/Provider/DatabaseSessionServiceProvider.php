<?php
// app/Module/Provider/DatabaseSessionServiceProvider.php
namespace Module\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Session\DatabaseSession;
use Core\Session\DatabaseSessionHandler;

class DatabaseSessionServiceProvider implements ServiceProvider
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
            $session->setDI($di);
            if ($di->has('eventsManager')) {
                $session->setEventsManager($di->get('eventsManager'));
            }
            return $session;
        });
    }
}