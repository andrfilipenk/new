<?php
// app/Base/Provider/ViewServiceProvider.php
namespace Base\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class ViewServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $view = $container->get('\Core\Mvc\View');
        $view->setLayout('default');
        $di = $container;
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
    }
}