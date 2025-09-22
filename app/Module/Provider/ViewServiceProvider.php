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
            $config = $di->get('config')['view'];
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