<?php
// app/Module/Provider/ChartServiceProvider.php
namespace Module\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Chart\ChartFactory;
use Core\Chart\ChartBuilder;

/**
 * Chart Service Provider for dependency injection registration
 * Following framework's service provider pattern
 */
class ChartServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Register chart factory
        $container->set('chartFactory', function($di) {
            $factory = new ChartFactory();
            $factory->setDI($di);
            return $factory;
        });

        // Register chart builder
        $container->set('chartBuilder', function() {
            return new ChartBuilder();
        });

        // Register as singleton
        $container->set(ChartFactory::class, function($di) {
            $factory = new ChartFactory();
            $factory->setDI($di);
            return $factory;
        });
    }
}