<?php

namespace Project;

use Core\Mvc\ModuleInterface;
use Core\Di\ServiceProviderInterface;

/**
 * Project Module
 * Handles project management, orders, positions, and KPI tracking
 */
class Module implements ModuleInterface
{
    /**
     * Get module configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/config.php';
    }

    /**
     * Get service providers for this module
     *
     * @return array
     */
    public function getServiceProviders(): array
    {
        return [];
    }

    /**
     * Initialize module
     * Called when module is loaded
     *
     * @param \Core\Di\Container $container
     * @return void
     */
    public function init($container): void
    {
        // Module initialization logic
        // Register event listeners, services, etc.
    }
}
