<?php
// app/Module/Provider/ViewServiceProvider.php
namespace Module\Base\Provider;

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
            $view->registerHelper('url', function ($to = null, $params = [], $reset = false) use ($di) {
                return $di->get('url')->get($to, $params, $reset);
            });
            $view->registerHelper('items', function ($items, $template) use ($view) {
                $result = '';
                foreach ($items as $item) {
                    $result .= $view->partial($template, ['item' => $item]);
                }
                return $result;
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