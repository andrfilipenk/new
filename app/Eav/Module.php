<?php
// app/Eav/Module.php
namespace Eav;

use Core\Di\Interface\Container;
use Core\Mvc\AbstractModule;

/**
 * EAV (Entity-Attribute-Value) Module
 * 
 * Provides a flexible, high-performance data modeling pattern that enables 
 * dynamic attribute management for entities without schema modifications.
 */
class Module extends AbstractModule
{
    /**
     * Boot the EAV module
     */
    public function boot(Container $di, $module, $controller, $action)
    {
        // Register EAV services during boot
        // Services will be registered by the EavServiceProvider
    }
}
