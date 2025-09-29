<?php
// app/Module/Provider/RouterServiceProvider.php
namespace Module\Base\Provider;

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
    }
}