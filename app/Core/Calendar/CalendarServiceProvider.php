<?php

namespace Core\Calendar;

use Core\Provider\ServiceProvider;
use Core\Di\Container;
use Core\Calendar\Styling\Themes\DefaultTheme;
use Core\Calendar\Styling\Themes\DarkTheme;
use Core\Calendar\Styling\Themes\MinimalTheme;
use Core\Calendar\Styling\Themes\ColorfulTheme;

/**
 * Service provider for calendar services
 */
class CalendarServiceProvider extends ServiceProvider
{
    /**
     * Register calendar services with the DI container
     */
    public function register(Container $container): void
    {
        // Register the calendar factory as singleton
        $container->singleton('calendar.factory', function ($container) {
            return new CalendarFactory();
        });

        // Register default theme
        $container->singleton('calendar.theme.default', function ($container) {
            return new DefaultTheme();
        });

        // Register dark theme
        $container->singleton('calendar.theme.dark', function ($container) {
            return new DarkTheme();
        });

        // Register minimal theme
        $container->singleton('calendar.theme.minimal', function ($container) {
            return new MinimalTheme();
        });

        // Register colorful theme
        $container->singleton('calendar.theme.colorful', function ($container) {
            return new ColorfulTheme();
        });
    }

    /**
     * Bootstrap calendar services
     */
    public function boot(Container $container): void
    {
        // Any bootstrapping logic can go here
        // For example, registering view composers or event listeners
    }
}
