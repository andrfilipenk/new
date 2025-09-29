<?php
// app/Base/Provider/NavigationServiceProvider.php
namespace Base\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;

class NavigationServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->set('navigation', function($di) {
            /** @var \Core\Utils\Url $url */
            $url        = $di->get('url');
            /** @var \Core\Http\Request $request */
            $request    = $di->get('request');
            $config     = $di->get('config');
            $navItems   = $config['navigation'];
            $navigation = [];
            foreach ($navItems as $label => $to) {
                $linkTo = $url->get($to);
                $isActive = $request->uri() === $linkTo;
                $navigation[] = [
                    'active' => $isActive,
                    'label'  => $label,
                    'url'    => $linkTo
                ];
            }
            return $navigation;
        });
    }
}