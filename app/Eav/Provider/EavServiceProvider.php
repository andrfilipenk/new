<?php
// app/Eav/Provider/EavServiceProvider.php
namespace Eav\Provider;

use Core\Di\Interface\Container;
use Core\Di\Interface\ServiceProvider;
use Eav\Config\ConfigLoader;
use Eav\Config\EntityTypeRegistry;

/**
 * EAV Service Provider
 * 
 * Registers EAV services in the dependency injection container.
 */
class EavServiceProvider implements ServiceProvider
{
    /**
     * Register EAV services
     */
    public function register(Container $di): void
    {
        // Register ConfigLoader
        $di->set('eav.config_loader', function($di) {
            $config = $di->get('config');
            $configPath = $config['eav']['config_path'] ?? __DIR__ . '/../Config/entities';
            return new ConfigLoader($configPath);
        });

        // Register EntityTypeRegistry
        $di->set('eav.entity_type_registry', function($di) {
            return new EntityTypeRegistry($di->get('eav.config_loader'));
        });

        // Note: Additional services (AttributeManager, ValueManager, EntityManager, etc.)
        // will be registered here as they are implemented
    }
}
