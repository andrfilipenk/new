<?php
// app/Core/Eav/Provider/EavServiceProvider.php
namespace Core\Eav\Provider;

use Core\Di\Interface\ServiceProvider;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Eav\Config\ConfigLoader;
use Core\Eav\Config\EntityTypeRegistry;
use Core\Eav\Schema\SchemaManager;
use Core\Eav\Schema\StructureBuilder;
use Core\Eav\Schema\MigrationGenerator;
use Core\Eav\Storage\EavTableStorage;
use Core\Eav\Storage\ValueTransformer;
use Core\Eav\Manager\EntityManager;
use Core\Eav\Manager\ValueManager;
use Core\Eav\Manager\AttributeMetadataManager;
use Core\Eav\Repository\EntityRepository;
use Core\Eav\Repository\AttributeRepository;

/**
 * EAV Service Provider - Registers all EAV components
 */
class EavServiceProvider implements ServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Phase 1: Configuration and Registry
        $container->set('eav.config_loader', function($di) {
            $configPath = __DIR__ . '/../../../../config/eav';
            return new ConfigLoader($configPath);
        });

        $container->set('eav.registry', function($di) {
            $configLoader = $di->get('eav.config_loader');
            return new EntityTypeRegistry($configLoader);
        });

        // Phase 2: Schema Management
        $container->set('eav.structure_builder', function($di) {
            return new StructureBuilder();
        });

        $container->set('eav.migration_generator', function($di) {
            $structureBuilder = $di->get('eav.structure_builder');
            $migrationsPath = __DIR__ . '/../../../../migrations';
            return new MigrationGenerator($structureBuilder, $migrationsPath);
        });

        $container->set('eav.schema_manager', function($di) {
            $db = $di->get('db');
            $structureBuilder = $di->get('eav.structure_builder');
            $migrationGenerator = $di->get('eav.migration_generator');
            
            $manager = new SchemaManager($db, $structureBuilder, $migrationGenerator);
            $manager->setDI($di);
            return $manager;
        });

        // Phase 2: Storage Strategy
        $container->set('eav.value_transformer', function($di) {
            return new ValueTransformer();
        });

        $container->set('eav.storage.eav', function($di) {
            $db = $di->get('db');
            $transformer = $di->get('eav.value_transformer');
            
            $storage = new EavTableStorage($db, $transformer);
            $storage->setDI($di);
            return $storage;
        });

        // Phase 2: Attribute Metadata Manager
        $container->set('eav.attribute_metadata_manager', function($di) {
            $db = $di->get('db');
            
            $manager = new AttributeMetadataManager($db);
            $manager->setDI($di);
            return $manager;
        });

        // Phase 2: Value Manager
        $container->set('eav.value_manager', function($di) {
            $storage = $di->get('eav.storage.eav');
            $attributeMetadata = $di->get('eav.attribute_metadata_manager');
            
            $manager = new ValueManager($storage, $attributeMetadata);
            $manager->setDI($di);
            return $manager;
        });

        // Phase 2: Entity Manager
        $container->set('eav.entity_manager', function($di) {
            $db = $di->get('db');
            $valueManager = $di->get('eav.value_manager');
            $registry = $di->get('eav.registry');
            
            $manager = new EntityManager($db, $valueManager, $registry);
            $manager->setDI($di);
            return $manager;
        });

        // Phase 2: Repositories
        $container->set('eav.entity_repository', function($di) {
            $db = $di->get('db');
            $entityManager = $di->get('eav.entity_manager');
            
            $repository = new EntityRepository($db, $entityManager);
            $repository->setDI($di);
            return $repository;
        });

        $container->set('eav.attribute_repository', function($di) {
            $db = $di->get('db');
            
            $repository = new AttributeRepository($db);
            $repository->setDI($di);
            return $repository;
        });
    }
}
