<?php
// app/Eav/Module.php
namespace Eav;

use Core\Di\Container;
use Core\Di\Injectable;

/**
 * EAV Module
 * 
 * Provides Entity-Attribute-Value model support with advanced entity management,
 * sophisticated query mechanisms, and performance optimization features.
 * 
 * @package Eav
 */
class Module
{
    use Injectable;

    /**
     * Register module services
     */
    public function registerServices(Container $di): void
    {
        // Register Entity Manager
        $di->setShared('eavEntityManager', function() use ($di) {
            return new Services\EntityManager(
                $di->get('db'),
                $di->get('eavValueRepository'),
                $di->get('eavAttributeRepository'),
                $di->get('eavCacheManager'),
                $di->get('eventsManager')
            );
        });

        // Register Value Repository
        $di->setShared('eavValueRepository', function() use ($di) {
            return new Repositories\ValueRepository(
                $di->get('db'),
                $di->get('eavStorageStrategy')
            );
        });

        // Register Attribute Repository
        $di->setShared('eavAttributeRepository', function() use ($di) {
            return new Repositories\AttributeRepository(
                $di->get('db'),
                $di->get('eavCacheManager')
            );
        });

        // Register Storage Strategy
        $di->setShared('eavStorageStrategy', function() use ($di) {
            return new Storage\StorageStrategyFactory($di->get('db'));
        });

        // Register Query Builder Factory
        $di->setShared('eavQueryFactory', function() use ($di) {
            return new Query\QueryFactory(
                $di->get('db'),
                $di->get('eavAttributeRepository'),
                $di->get('eavJoinOptimizer'),
                $di->get('eavFilterTranslator')
            );
        });

        // Register Join Optimizer
        $di->setShared('eavJoinOptimizer', function() {
            return new Query\JoinOptimizer();
        });

        // Register Filter Translator
        $di->setShared('eavFilterTranslator', function() {
            return new Query\FilterTranslator();
        });

        // Register Cache Manager
        $di->setShared('eavCacheManager', function() use ($di) {
            return new Cache\CacheManager($di->get('db'));
        });

        // Register Batch Manager
        $di->setShared('eavBatchManager', function() use ($di) {
            return new Services\BatchManager(
                $di->get('db'),
                $di->get('eavValueRepository')
            );
        });

        // Register Index Manager
        $di->setShared('eavIndexManager', function() use ($di) {
            return new Services\IndexManager($di->get('db'));
        });

        // Register Query Cache
        $di->setShared('eavQueryCache', function() use ($di) {
            return new Cache\QueryCache(
                $di->get('db'),
                $di->get('eavCacheManager')
            );
        });

        // Register Entity Repository
        $di->setShared('eavEntityRepository', function() use ($di) {
            return new Repositories\EntityRepository(
                $di->get('eavEntityManager'),
                $di->get('eavQueryFactory'),
                $di->get('eavQueryCache')
            );
        });
    }

    /**
     * Module boot method
     */
    public function boot(): void
    {
        // Register event listeners for cache invalidation
        $eventsManager = $this->getDI()->get('eventsManager');
        
        $eventsManager->attach('eav:entity:created', function($event, $entity) {
            $this->getDI()->get('eavCacheManager')->invalidateEntity($entity->getId());
        });

        $eventsManager->attach('eav:entity:updated', function($event, $entity) {
            $this->getDI()->get('eavCacheManager')->invalidateEntity($entity->getId());
        });

        $eventsManager->attach('eav:entity:deleted', function($event, $entity) {
            $this->getDI()->get('eavCacheManager')->invalidateEntity($entity->getId());
        });
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return [
            'Core' => '>=1.0.0',
        ];
    }
}
