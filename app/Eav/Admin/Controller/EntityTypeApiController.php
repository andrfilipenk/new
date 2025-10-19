<?php

namespace Eav\Admin\Controller;

use Core\Mvc\Controller;
use Eav\Admin\Service\AdminService;
use Eav\Admin\Service\APIService;
use Eav\Admin\Service\AuditLoggingService;

class EntityTypeApiController extends Controller
{
    private AdminService $adminService;
    private APIService $apiService;
    private AuditLoggingService $auditService;
    
    public function __construct()
    {
        parent::__construct();
        
        // Initialize services via DI
        $this->adminService = $this->container->get(AdminService::class);
        $this->apiService = $this->container->get(APIService::class);
        $this->auditService = $this->container->get(AuditLoggingService::class);
    }
    
    /**
     * GET /api/v1/eav/entity-types
     * List all entity types
     */
    public function index()
    {
        try {
            $page = (int)($this->request->query('page') ?? 1);
            $limit = (int)($this->request->query('limit') ?? 25);
            $search = $this->request->query('search');
            $status = $this->request->query('status');
            
            $result = $this->adminService->getEntityTypes($page, $limit, $search, $status);
            
            $this->auditService->log(
                'entity_type.list',
                null,
                null,
                $this->request->getAttribute('user_id'),
                ['page' => $page, 'limit' => $limit],
                200
            );
            
            return $this->jsonResponse($this->apiService->successResponse($result['data'], $result['meta']));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * GET /api/v1/eav/entity-types/{code}
     * Get specific entity type
     */
    public function show(string $code)
    {
        try {
            $entityType = $this->adminService->getEntityType($code);
            
            if (!$entityType) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('RESOURCE_NOT_FOUND', "Entity type '{$code}' not found"),
                    404
                );
            }
            
            $this->auditService->log(
                'entity_type.read',
                $code,
                null,
                $this->request->getAttribute('user_id'),
                null,
                200
            );
            
            return $this->jsonResponse($this->apiService->successResponse($entityType));
            
        } catch (\Exception $e) {
            return $this->jsonResponse(
                $this->apiService->errorResponse('SERVER_ERROR', $e->getMessage()),
                500
            );
        }
    }
    
    /**
     * POST /api/v1/eav/entity-types
     * Create new entity type
     */
    public function store()
    {
        try {
            $data = $this->getJsonInput();
            
            // Validate required fields
            $required = ['entity_type_code', 'entity_type_label', 'entity_table'];
            $missing = [];
            
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missing[] = ['field' => $field, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
                }
            }
            
            if (!empty($missing)) {
                return $this->jsonResponse(
                    $this->apiService->errorResponse('VALIDATION_ERROR', 'Validation failed', $missing),
                    400
                );
            }
            
            $userId = $this->request->getAttribute('user_id');
            $entityType = $this->adminService->createEntityType($data, $userId);
            
            return $this->jsonResponse(
                $this->apiService->successResponse($entityType, null, 'Entity type created successfully'),
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
     * PUT /api/v1/eav/entity-types/{code}
     * Update entity type
     */
    public function update(string $code)
    {
        try {
            $data = $this->getJsonInput();
            $userId = $this->request->getAttribute('user_id');
            
            $entityType = $this->adminService->updateEntityType($code, $data, $userId);
            
            return $this->jsonResponse(
                $this->apiService->successResponse($entityType, null, 'Entity type updated successfully')
            );
            
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === "Entity type '{$code}' not found" ? 404 : 500;
            
            return $this->jsonResponse(
                $this->apiService->errorResponse(
                    $statusCode === 404 ? 'RESOURCE_NOT_FOUND' : 'SERVER_ERROR',
                    $e->getMessage()
                ),
                $statusCode
            );
        }
    }
    
    /**
     * DELETE /api/v1/eav/entity-types/{code}
     * Delete entity type
     */
    public function destroy(string $code)
    {
        try {
            $userId = $this->request->getAttribute('user_id');
            $this->adminService->deleteEntityType($code, $userId);
            
            return $this->jsonResponse(
                $this->apiService->successResponse(null, null, 'Entity type deleted successfully')
            );
            
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === "Entity type '{$code}' not found" ? 404 : 500;
            
            return $this->jsonResponse(
                $this->apiService->errorResponse(
                    $statusCode === 404 ? 'RESOURCE_NOT_FOUND' : 'SERVER_ERROR',
                    $e->getMessage()
                ),
                $statusCode
            );
        }
    }
    
    /**
     * GET /api/v1/eav/entity-types/{code}/attributes
     * Get attributes for entity type
     */
    public function attributes(string $code)
    {
        try {
            $attributes = $this->adminService->getAttributesForType($code);
            
            return $this->jsonResponse($this->apiService->successResponse($attributes));
            
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === "Entity type '{$code}' not found" ? 404 : 500;
            
            return $this->jsonResponse(
                $this->apiService->errorResponse(
                    $statusCode === 404 ? 'RESOURCE_NOT_FOUND' : 'SERVER_ERROR',
                    $e->getMessage()
                ),
                $statusCode
            );
        }
    }
    
    /**
     * GET /api/v1/eav/entity-types/{code}/stats
     * Get statistics for entity type
     */
    public function stats(string $code)
    {
        try {
            $stats = $this->adminService->getEntityTypeStats($code);
            
            return $this->jsonResponse($this->apiService->successResponse($stats));
            
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === "Entity type '{$code}' not found" ? 404 : 500;
            
            return $this->jsonResponse(
                $this->apiService->errorResponse(
                    $statusCode === 404 ? 'RESOURCE_NOT_FOUND' : 'SERVER_ERROR',
                    $e->getMessage()
                ),
                $statusCode
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
