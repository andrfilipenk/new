<?php
namespace Core\View;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $di): void
    {
        $di->set('view', function() use ($di) {
            $config = $di->get('config')['view'];
            
            $view = new View($config['path'], $config['cachePath'] ?? null);
            
            // Set default layout if configured
            if (isset($config['layout'])) {
                $view->setLayout($config['layout']);
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

// example usage view service provider
// Register the service provider in your application bootstrap or configuration
// add this line in your bootstrap file after creating the DI container
// $di->register(new \Core\View\ViewServiceProvider());
// example usage in a controller
// $this->di->get('view')->render('template', ['var' => 'value']);