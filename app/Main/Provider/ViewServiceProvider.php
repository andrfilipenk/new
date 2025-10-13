<?php
// app/Main/Provider/ViewServiceProvider.php
namespace Main\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Mvc\View;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('uiLayout', '\Core\UI\Layout');
        
        $container->set('view', function($di) {
            $view = new View();
            $view->setLayout('default');
            $view->setDI($di);

            $view->registerHelper('url', function($to = null, $params = [], $reset = false) use ($di) {
                return $di->get('url')->get($to, $params, $reset);
            });

            $view->registerHelper('messages', function() use ($di) {
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

        $em = $container->get('eventsManager');
        $em->attach('module.afterInitialize', function($e) use ($container) {
            $module = $e->getData();
            if ($navItems = $module->getConfig('navbar')) {
                $uiLayout = $container->get('uiLayout');
                foreach ($navItems as $item) {
                    $uiLayout->addNav($item['label'], $item['icon'], $item['url']);
                }
            }
        });
    }
}