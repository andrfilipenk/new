<?php

namespace Eav\Admin\Provider;

use Core\Provider\ServiceProvider;
use Eav\Admin\Service\AdminService;
use Eav\Admin\Service\APIService;
use Eav\Admin\Service\ValidationService;
use Eav\Admin\Service\AuditLoggingService;
use Eav\Admin\Service\VersioningService;
use Eav\Admin\Service\ImportExportService;
use Eav\Admin\Service\ReportingEngine;
use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;
use Eav\Services\EntityService;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        // Register AuditLoggingService
        $this->container->singleton(AuditLoggingService::class, function($container) {
            $config = $container->get('config')['eav_admin']['audit'] ?? [];
            return new AuditLoggingService($config);
        });
        
        // Register ValidationService
        $this->container->singleton(ValidationService::class, function($container) {
            return new ValidationService(
                $container->get(EntityTypeRepository::class),
                $container->get(AttributeRepository::class)
            );
        });
        
        // Register AdminService
        $this->container->singleton(AdminService::class, function($container) {
            return new AdminService(
                $container->get(EntityTypeRepository::class),
                $container->get(AttributeRepository::class),
                $container->get(AuditLoggingService::class)
            );
        });
        
        // Register APIService
        $this->container->singleton(APIService::class, function($container) {
            return new APIService(
                $container->get(EntityTypeRepository::class),
                $container->get(AttributeRepository::class),
                $container->get(EntityService::class),
                $container->get(AuditLoggingService::class),
                $container->get(ValidationService::class)
            );
        });
        
        // Register VersioningService
        $this->container->singleton(VersioningService::class, function($container) {
            $config = $container->get('config')['eav_admin']['versioning'] ?? [];
            return new VersioningService(
                $container->get(EntityService::class),
                $config
            );
        });
        
        // Register ImportExportService
        $this->container->singleton(ImportExportService::class, function($container) {
            $config = $container->get('config')['eav_admin']['import'] ?? [];
            return new ImportExportService(
                $container->get(EntityService::class),
                $container->get(EntityTypeRepository::class),
                $container->get(AttributeRepository::class),
                $container->get(ValidationService::class),
                $config
            );
        });
        
        // Register ReportingEngine
        $this->container->singleton(ReportingEngine::class, function($container) {
            return new ReportingEngine(
                $container->get(EntityService::class),
                $container->get(EntityTypeRepository::class),
                $container->get(AttributeRepository::class)
            );
        });
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register event listeners for audit logging
        $this->registerAuditListeners();
        
        // Register versioning hooks
        $this->registerVersioningHooks();
    }
    
    /**
     * Register audit logging event listeners
     */
    private function registerAuditListeners(): void
    {
        $events = $this->container->get('events');
        $auditService = $this->container->get(AuditLoggingService::class);
        
        // Entity events
        $events->listen('entity.created', function($entity, $entityType) use ($auditService) {
            $auditService->log(
                'entity.create',
                $entityType,
                $entity->entity_id ?? null,
                $_SESSION['user_id'] ?? null,
                ['entity_id' => $entity->entity_id],
                201
            );
        });
        
        $events->listen('entity.updated', function($entity, $entityType) use ($auditService) {
            $auditService->log(
                'entity.update',
                $entityType,
                $entity->entity_id ?? null,
                $_SESSION['user_id'] ?? null,
                ['entity_id' => $entity->entity_id],
                200
            );
        });
        
        $events->listen('entity.deleted', function($entityId, $entityType) use ($auditService) {
            $auditService->log(
                'entity.delete',
                $entityType,
                $entityId,
                $_SESSION['user_id'] ?? null,
                ['entity_id' => $entityId],
                200
            );
        });
    }
    
    /**
     * Register versioning hooks
     */
    private function registerVersioningHooks(): void
    {
        $events = $this->container->get('events');
        $versioningService = $this->container->get(VersioningService::class);
        
        // Create version on entity update
        $events->listen('entity.updated', function($entity, $entityType) use ($versioningService) {
            try {
                // Get entity type ID
                $entityTypeRepo = $this->container->get(EntityTypeRepository::class);
                $type = $entityTypeRepo->findByCode($entityType);
                
                if ($type) {
                    $versioningService->createVersion(
                        $entity->entity_id,
                        $type->entity_type_id,
                        $entity->attributes ?? [],
                        null,
                        $_SESSION['user_id'] ?? null,
                        'Auto-versioned on update'
                    );
                }
            } catch (\Exception $e) {
                // Log error but don't fail the update
                error_log("Versioning failed: " . $e->getMessage());
            }
        });
    }
}
