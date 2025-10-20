<?php
// app/Core/Eav/EavServiceProvider.php
namespace Core\Eav;

use Core\Di\Container;
use Core\Eav\Entity\EntityManager;
use Core\Eav\Storage\EavStorageStrategy;
use Core\Eav\Storage\StorageStrategy;

/**
 * EAV Service Provider
 * 
 * Registers EAV services in the DI container
 */
class EavServiceProvider
{
    /**
     * Register services
     * 
     * @param Container $container
     */
    public function register(Container $container): void
    {
        // Register storage strategy
        $container->set(StorageStrategy::class, function ($c) {
            return new EavStorageStrategy();
        });

        // Register Entity Manager
        $container->set(EntityManager::class, function ($c) {
            $storage = $c->get(StorageStrategy::class);
            return new EntityManager($storage);
        });

        // Register shorthand aliases
        $container->set('eavEntityManager', function ($c) {
            return $c->get(EntityManager::class);
        });

        $container->set('eavStorage', function ($c) {
            return $c->get(StorageStrategy::class);
        });
    }

    /**
     * Boot services (called after all providers registered)
     * 
     * @param Container $container
     */
    public function boot(Container $container): void
    {
        // Register event listeners if needed
        $eventsManager = $container->get('eventsManager');
        
        if ($eventsManager) {
            // Could register cache invalidation listeners here
            // $eventsManager->attach('entity.after_save', new CacheInvalidationListener());
        }
    }
}
