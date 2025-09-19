<?php
// app/Module/Provider/RouterServiceProvider.php
namespace Module\Provider;

use Core\Cookie\Cookie;
use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class RouterServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('router', function($di) {
            $config = $di->get('config');
            $router = new \Core\Mvc\Router();
            if (isset($config['modules'])) {
                foreach ($config['modules'] as $module) {
                    if (isset($module['routes'])) {
                        foreach ($module['routes'] as $pattern => $routeConfig) {
                            $router->add($pattern, $routeConfig);
                        }
                    }
                }
            }
            return $router;
        });
        
        
        $container->set('cookie', function($di) {
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