<?php

namespace Eav\Admin\Service;

use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;
use Eav\Admin\Models\AuditLog;

class AdminService
{
    private EntityTypeRepository $entityTypeRepo;
    private AttributeRepository $attributeRepo;
    private AuditLoggingService $auditService;
    
    public function __construct(
        EntityTypeRepository $entityTypeRepo,
        AttributeRepository $attributeRepo,
        AuditLoggingService $auditService
    ) {
        $this->entityTypeRepo = $entityTypeRepo;
        $this->attributeRepo = $attributeRepo;
        $this->auditService = $auditService;
    }
    
    /**
     * Get all entity types with pagination
     */
    public function getEntityTypes(int $page = 1, int $limit = 25, ?string $search = null, ?string $status = null): array
    {
        $query = $this->entityTypeRepo->query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->orWhere('entity_type_code', 'LIKE', "%{$search}%")
                  ->orWhere('entity_type_label', 'LIKE', "%{$search}%");
            });
        }
        
        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('is_active', 0);
        }
        
        $total = $query->count();
        $offset = ($page - 1) * $limit;
        
        $entityTypes = $query->limit($limit)->offset($offset)->get();
        
        return [
            'data' => $entityTypes,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Get entity type by code
     */
    public function getEntityType(string $code): ?object
    {
        return $this->entityTypeRepo->findByCode($code);
    }
    
    /**
     * Create new entity type
     */
    public function createEntityType(array $data, int $userId): object
    {
        $entityType = $this->entityTypeRepo->create($data);
        
        $this->auditService->log(
            'entity_type.create',
            null,
            null,
            $userId,
            $data
        );
        
        return $entityType;
    }
    
    /**
     * Update entity type
     */
    public function updateEntityType(string $code, array $data, int $userId): object
    {
        $entityType = $this->entityTypeRepo->findByCode($code);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$code}' not found");
        }
        
        $updated = $this->entityTypeRepo->update($entityType->entity_type_id, $data);
        
        $this->auditService->log(
            'entity_type.update',
            $code,
            $entityType->entity_type_id,
            $userId,
            $data
        );
        
        return $updated;
    }
    
    /**
     * Delete entity type
     */
    public function deleteEntityType(string $code, int $userId): bool
    {
        $entityType = $this->entityTypeRepo->findByCode($code);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$code}' not found");
        }
        
        $result = $this->entityTypeRepo->delete($entityType->entity_type_id);
        
        $this->auditService->log(
            'entity_type.delete',
            $code,
            $entityType->entity_type_id,
            $userId,
            ['code' => $code]
        );
        
        return $result;
    }
    
    /**
     * Get attributes for entity type
     */
    public function getAttributesForType(string $entityTypeCode): array
    {
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$entityTypeCode}' not found");
        }
        
        return $this->attributeRepo->getByEntityType($entityType->entity_type_id);
    }
    
    /**
     * Get entity type statistics
     */
    public function getEntityTypeStats(string $code): array
    {
        $entityType = $this->entityTypeRepo->findByCode($code);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$code}' not found");
        }
        
        // Get entity count
        $entityCount = \Core\Database\DB::table($entityType->entity_table)->count();
        
        // Get attribute count
        $attributeCount = count($this->attributeRepo->getByEntityType($entityType->entity_type_id));
        
        // Get storage size (estimate)
        $storageSize = $this->estimateStorageSize($entityType);
        
        return [
            'entity_count' => $entityCount,
            'attribute_count' => $attributeCount,
            'storage_size_mb' => $storageSize,
            'storage_strategy' => $entityType->storage_strategy,
            'is_active' => $entityType->is_active
        ];
    }
    
    /**
     * Estimate storage size for entity type
     */
    private function estimateStorageSize(object $entityType): float
    {
        $tables = [$entityType->entity_table];
        
        if ($entityType->storage_strategy === 'eav') {
            $tables = array_merge($tables, [
                'eav_entity_varchar',
                'eav_entity_int',
                'eav_entity_decimal',
                'eav_entity_datetime',
                'eav_entity_text'
            ]);
        }
        
        $totalSize = 0;
        foreach ($tables as $table) {
            $result = \Core\Database\DB::raw(
                "SELECT (data_length + index_length) / 1024 / 1024 AS size 
                 FROM information_schema.TABLES 
                 WHERE table_schema = DATABASE() AND table_name = ?",
                [$table]
            );
            
            if ($result && isset($result[0])) {
                $totalSize += $result[0]->size ?? 0;
            }
        }
        
        return round($totalSize, 2);
    }
}
