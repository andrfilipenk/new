<?php
// app/Module/Provider/ViewServiceProvider.php
namespace Module\Provider;

use Core\Mvc\View;
use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('view', function($di) {
            $config = $di->get('config');
            $view = new View($config['view']);
            $view->setDI($di);
            if ($di->has('eventsManager')) {
                $view->setEventsManager($di->get('eventsManager'));
            }
            // New: Register 'url' helper
            $view->registerHelper('url', function ($to = null) use ($di) {
                return $di->get('url')->get($to);
            });
            $view->registerHelper('messages', function () use ($di) {
                $messages = [];
                $session  = $di->get('session');
                if ($session->has('messages')) {
                    $messages = $session->get('messages');
                    $session->remove('messages');
                }
                return $messages;
            });
            return $view;
        });
    }
}