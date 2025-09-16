<?php
// app/Module/Provider/ViewServiceProvider.php
namespace Module\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $di): void
    {
        $di->set('view', function() use ($di) {
            $config = $di->get('config')['view'];
            // The constructor now only needs the template path.
            $view = new View($config['path']);
            if (isset($config['layout'])) {
                $view->setLayout($config['layout']);
            }
            $view->setDI($di);
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