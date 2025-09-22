<?php
// app/Module/Provider/CookieServiceProvider.php
namespace Module\Provider;

use Core\Cookie\Cookie;
use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class CookieServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('cookie', function($di) {
            $config = $di->get('config');
            $cookieConfig = $config['cookie'] ?? [];
            $cookie = new Cookie($cookieConfig);
            $cookie->setDI($di);
            if ($di->has('eventsManager')) {
                $cookie->setEventsManager($di->get('eventsManager'));
            }
            return $cookie;
        });
    }
}