<?php

namespace Eav\Admin\Service;

use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;
use Eav\Services\EntityService;
use Eav\Admin\Models\AuditLog;

class APIService
{
    private EntityTypeRepository $entityTypeRepo;
    private AttributeRepository $attributeRepo;
    private EntityService $entityService;
    private AuditLoggingService $auditService;
    private ValidationService $validationService;
    
    public function __construct(
        EntityTypeRepository $entityTypeRepo,
        AttributeRepository $attributeRepo,
        EntityService $entityService,
        AuditLoggingService $auditService,
        ValidationService $validationService
    ) {
        $this->entityTypeRepo = $entityTypeRepo;
        $this->attributeRepo = $attributeRepo;
        $this->entityService = $entityService;
        $this->auditService = $auditService;
        $this->validationService = $validationService;
    }
    
    /**
     * Format success response
     */
    public function successResponse($data, ?array $meta = null, ?string $message = null): array
    {
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if ($meta !== null) {
            $response['meta'] = $meta;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $response;
    }
    
    /**
     * Format error response
     */
    public function errorResponse(string $code, string $message, ?array $details = null, int $httpStatus = 400): array
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        return $response;
    }
    
    /**
     * Search entities with advanced filtering
     */
    public function searchEntities(string $entityTypeCode, array $searchParams, int $userId): array
    {
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        
        if (!$entityType) {
            throw new \Exception("Entity type '{$entityTypeCode}' not found", 404);
        }
        
        // Extract search parameters
        $filters = $searchParams['filters'] ?? [];
        $sort = $searchParams['sort'] ?? [];
        $pagination = $searchParams['pagination'] ?? ['page' => 1, 'limit' => 50];
        $includeAttributes = $searchParams['include_attributes'] ?? null;
        
        // Build query using EntityService
        $query = $this->entityService->query($entityTypeCode);
        
        // Apply filters
        foreach ($filters as $filter) {
            $attribute = $filter['attribute'];
            $operator = $filter['operator'];
            $value = $filter['value'];
            
            switch ($operator) {
                case 'equals':
                case '=':
                    $query->where($attribute, '=', $value);
                    break;
                case 'like':
                    $query->where($attribute, 'LIKE', $value);
                    break;
                case 'in':
                    $query->whereIn($attribute, $value);
                    break;
                case 'between':
                    $query->whereBetween($attribute, $value);
                    break;
                case '>':
                case '<':
                case '>=':
                case '<=':
                case '!=':
                    $query->where($attribute, $operator, $value);
                    break;
            }
        }
        
        // Apply sorting
        foreach ($sort as $sortRule) {
            $query->orderBy($sortRule['attribute'], $sortRule['direction'] ?? 'asc');
        }
        
        // Get total count
        $total = $query->count();
        
        // Apply pagination
        $page = $pagination['page'] ?? 1;
        $limit = min($pagination['limit'] ?? 50, 200); // Max 200 per page
        $offset = ($page - 1) * $limit;
        
        $entities = $query->limit($limit)->offset($offset)->get();
        
        // Format entities
        $formattedEntities = [];
        foreach ($entities as $entity) {
            $formattedEntity = [
                'id' => $entity->entity_id,
                'entity_type' => $entityTypeCode,
                'attributes' => []
            ];
            
            // Filter attributes if specified
            foreach ($entity->attributes as $code => $value) {
                if ($includeAttributes === null || in_array($code, $includeAttributes)) {
                    $formattedEntity['attributes'][$code] = $value;
                }
            }
            
            $formattedEntity['created_at'] = $entity->created_at ?? null;
            $formattedEntity['updated_at'] = $entity->updated_at ?? null;
            
            $formattedEntities[] = $formattedEntity;
        }
        
        // Log the search
        $this->auditService->log(
            'entity.search',
            $entityTypeCode,
            null,
            $userId,
            $searchParams
        );
        
        return [
            'data' => $formattedEntities,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Bulk create entities
     */
    public function bulkCreateEntities(string $entityTypeCode, array $entitiesData, int $userId): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($entitiesData as $index => $entityData) {
            try {
                // Validate
                $validation = $this->validationService->validateEntityData($entityTypeCode, $entityData);
                
                if (!$validation['valid']) {
                    $failed++;
                    $errors[] = [
                        'index' => $index,
                        'errors' => $validation['errors']
                    ];
                    continue;
                }
                
                // Create entity
                $entity = $this->entityService->create($entityTypeCode, $entityData);
                $results[] = [
                    'index' => $index,
                    'id' => $entity->entity_id,
                    'status' => 'success'
                ];
                $successful++;
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Log bulk operation
        $this->auditService->log(
            'entity.bulk_create',
            $entityTypeCode,
            null,
            $userId,
            [
                'total' => count($entitiesData),
                'successful' => $successful,
                'failed' => $failed
            ]
        );
        
        return [
            'results' => $results,
            'summary' => [
                'total' => count($entitiesData),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors
            ]
        ];
    }
    
    /**
     * Bulk update entities
     */
    public function bulkUpdateEntities(string $entityTypeCode, array $updates, int $userId): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($updates as $update) {
            $entityId = $update['id'] ?? null;
            $data = $update['data'] ?? [];
            
            if (!$entityId) {
                $failed++;
                $errors[] = [
                    'entity_id' => null,
                    'error' => 'Entity ID is required'
                ];
                continue;
            }
            
            try {
                // Validate
                $validation = $this->validationService->validateEntityData($entityTypeCode, $data, $entityId);
                
                if (!$validation['valid']) {
                    $failed++;
                    $errors[] = [
                        'entity_id' => $entityId,
                        'errors' => $validation['errors']
                    ];
                    continue;
                }
                
                // Update entity
                $entity = $this->entityService->update($entityId, $entityTypeCode, $data);
                $results[] = [
                    'id' => $entityId,
                    'status' => 'success'
                ];
                $successful++;
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'entity_id' => $entityId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Log bulk operation
        $this->auditService->log(
            'entity.bulk_update',
            $entityTypeCode,
            null,
            $userId,
            [
                'total' => count($updates),
                'successful' => $successful,
                'failed' => $failed
            ]
        );
        
        return [
            'results' => $results,
            'summary' => [
                'total' => count($updates),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors
            ]
        ];
    }
}
