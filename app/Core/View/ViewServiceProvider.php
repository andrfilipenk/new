<?php
namespace Core\View;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $di): void
    {
        $di->set('view', function() use ($di) {
            $config = $di->get('config');
            $view = new View($config['view']['path']);
            
            // Set default layout if configured
            if (isset($config['view']['layout'])) {
                $view->setLayout($config['view']['layout']);
            }
            
            // Inject DI container
            $view->setDI($di);
            
            // Inject events manager if available
            if ($di->has('eventsManager')) {
                $view->setEventsManager($di->get('eventsManager'));
            }
            
            return $view;
        });
    }
}