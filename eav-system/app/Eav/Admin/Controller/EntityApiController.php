<?php

namespace Eav\Admin\Controller;

use Core\Mvc\Controller;
use Eav\Admin\Service\APIService;
use Eav\Admin\Service\ValidationService;
use Eav\Admin\Service\AuditLoggingService;
use Eav\Services\EntityService;

class EntityApiController extends Controller
{
    private EntityService $entityService;
    private APIService $apiService;
    private ValidationService $validationService;
    private AuditLoggingService $auditService;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->entityService = $this->container->get(EntityService::class);
        $this->apiService = $this->container->get(APIService::class);
        $this->validationService = $this->container->get(ValidationService::class);
        $this->auditService = $this->container->get(AuditLoggingService::class);
    }
    
    /**
     * GET /api/v1/eav/entities/{entityType}
     * List entities of type
     */
    public function index(string $entityType)
    {
        try {
            $page = (int)($this->request->query('page') ?? 1);
            $limit = min((int)($this->request->query('limit') ?? 50), 200);
            $search = $this->request->query('search');
            $sort = $this->request->query('sort');
            
            $query = $this->entityService->query($entityType);
            
            // Apply search if provided
            if ($search) {
                // Simple search across text fields
                $query->where('name', 'LIKE', "%{$search}%");
            }
            
            // Apply sorting
            if ($sort) {
                $direction = $this->request->query('direction') ?? 'asc';
                $query->orderBy($sort, $direction);
            }
            
            // Get total
            $total = $query->count();
            
            // Apply pagination
            $offset = ($page - 1) * $limit;
            $entities = $query->limit($limit)->offset($offset)->get();
            
            // Format entities
            $formattedEntities = array_map(function($entity) use ($entityType) {
                return [
                    'id' => $entity->entity_id,
                    'entity_type' => $entityType,
                    'attributes' => $entity->attributes,
                    'created_at' => $entity->created_at ?? null,
                    'updated_at' => $entity->updated_at ?? null
                ];
            }, $entities);
            
            return $this->jsonResponse($this->apiService->successResponse($formattedEntities, [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * GET /api/v1/eav/entities/{entityType}/{id}
     * Get single entity
     */
    public function show(string $entityType, int $id)
    {
        try {
            $entity = $this->entityService->load($id, $entityType);
            
            if (!$entity) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('RESOURCE_NOT_FOUND', 'Entity not found'),
                    404
                );
            }
            
            $formattedEntity = [
                'id' => $entity->entity_id,
                'entity_type' => $entityType,
                'attributes' => $entity->attributes,
                'created_at' => $entity->created_at ?? null,
                'updated_at' => $entity->updated_at ?? null
            ];
            
            return $this->jsonResponse($this->apiService->successResponse($formattedEntity));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * POST /api/v1/eav/entities/{entityType}
     * Create entity
     */
    public function store(string $entityType)
    {
        try {
            $data = $this->getJsonInput();
            
            // Validate
            $validation = $this->validationService->validateEntityData($entityType, $data);
            
            if (!$validation['valid']) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('VALIDATION_ERROR', 'Validation failed', $validation['errors']),
                    400
                );
            }
            
            $entity = $this->entityService->create($entityType, $data);
            
            $userId = $this->request->getAttribute('user_id');
            $this->auditService->log(
                'entity.create',
                $entityType,
                $entity->entity_id,
                $userId,
                $data,
                201
            );
            
            return $this->jsonResponse(
                $this->apiService->successResponse([
                    'id' => $entity->entity_id,
                    'entity_type' => $entityType,
                    'attributes' => $entity->attributes
                ], null, 'Entity created successfully'),
                201
            );
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * PUT /api/v1/eav/entities/{entityType}/{id}
     * Update entity
     */
    public function update(string $entityType, int $id)
    {
        try {
            $data = $this->getJsonInput();
            
            // Validate
            $validation = $this->validationService->validateEntityData($entityType, $data, $id);
            
            if (!$validation['valid']) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('VALIDATION_ERROR', 'Validation failed', $validation['errors']),
                    400
                );
            }
            
            $entity = $this->entityService->update($id, $entityType, $data);
            
            $userId = $this->request->getAttribute('user_id');
            $this->auditService->log(
                'entity.update',
                $entityType,
                $id,
                $userId,
                $data,
                200
            );
            
            return $this->jsonResponse(
                $this->apiService->successResponse([
                    'id' => $entity->entity_id,
                    'entity_type' => $entityType,
                    'attributes' => $entity->attributes
                ], null, 'Entity updated successfully')
            );
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * DELETE /api/v1/eav/entities/{entityType}/{id}
     * Delete entity
     */
    public function destroy(string $entityType, int $id)
    {
        try {
            $soft = $this->request->query('soft') === 'true';
            
            $this->entityService->delete($id, $entityType);
            
            $userId = $this->request->getAttribute('user_id');
            $this->auditService->log(
                'entity.delete',
                $entityType,
                $id,
                $userId,
                ['soft_delete' => $soft],
                200
            );
            
            return $this->jsonResponse(
                $this->apiService->successResponse(null, null, 'Entity deleted successfully')
            );
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * POST /api/v1/eav/entities/{entityType}/search
     * Advanced search
     */
    public function search(string $entityType)
    {
        try {
            $searchParams = $this->getJsonInput();
            $userId = $this->request->getAttribute('user_id');
            
            $result = $this->apiService->searchEntities($entityType, $searchParams, $userId);
            
            return $this->jsonResponse($this->apiService->successResponse($result['data'], $result['meta']));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * POST /api/v1/eav/entities/{entityType}/bulk
     * Bulk create entities
     */
    public function bulkCreate(string $entityType)
    {
        try {
            $input = $this->getJsonInput();
            $entities = $input['entities'] ?? [];
            
            if (empty($entities)) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('VALIDATION_ERROR', 'No entities provided'),
                    400
                );
            }
            
            $userId = $this->request->getAttribute('user_id');
            $result = $this->apiService->bulkCreateEntities($entityType, $entities, $userId);
            
            return $this->jsonResponse($this->apiService->successResponse($result));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * PUT /api/v1/eav/entities/{entityType}/bulk
     * Bulk update entities
     */
    public function bulkUpdate(string $entityType)
    {
        try {
            $input = $this->getJsonInput();
            $updates = $input['updates'] ?? [];
            
            if (empty($updates)) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('VALIDATION_ERROR', 'No updates provided'),
                    400
                );
            }
            
            $userId = $this->request->getAttribute('user_id');
            $result = $this->apiService->bulkUpdateEntities($entityType, $updates, $userId);
            
            return $this->jsonResponse($this->apiService->successResponse($result));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON input: ' . json_last_error_msg());
        }
        
        return $data ?? [];
    }
    
    /**
     * Return JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setStatusCode($statusCode);
        $this->response->setContent(json_encode($data));
        
        return $this->response;
    }
}
