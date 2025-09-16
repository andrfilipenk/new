<?php
// app/Module/Povider/CookieServiceProvider.php
namespace Module\Povider;

use Core\Cookie\Cookie;
use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container;

class CookieServiceProvider implements ServiceProvider
{
    public function register(Container $di): void
    {
        $di->set('cookie', function() use ($di) {
            $config = $di->get('config');
            $cookieConfig = $config['cookie'] ?? [];
            $cookie = new Cookie($cookieConfig);
            // Inject DI container
            $cookie->setDI($di);
            // Inject events manager if available
            if ($di->has('eventsManager')) {
                $cookie->setEventsManager($di->get('eventsManager'));
            }
            return $cookie;
        });
    }
}