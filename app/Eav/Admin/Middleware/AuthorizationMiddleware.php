<?php

namespace Eav\Admin\Middleware;

use Eav\Admin\Models\UserPermission;
use Eav\Admin\Models\ApiToken;
use Core\Http\Request;
use Core\Http\Response;

class AuthorizationMiddleware
{
    private array $requiredPermissions;
    
    public function __construct(array $requiredPermissions = [])
    {
        $this->requiredPermissions = $requiredPermissions;
    }
    
    /**
     * Handle authorization check
     */
    public function handle(Request $request, callable $next)
    {
        $userId = $request->getAttribute('user_id');
        
        if (!$userId) {
            return $this->forbiddenResponse('User not authenticated');
        }
        
        // Check if using API token
        $apiToken = $request->getAttribute('api_token');
        
        if ($apiToken) {
            // Check token scopes
            foreach ($this->requiredPermissions as $permission) {
                if (!$apiToken->hasScope($permission)) {
                    return $this->forbiddenResponse("Insufficient permissions: {$permission} scope required");
                }
            }
        } else {
            // Check user permissions
            $hasPermission = $this->checkUserPermissions($userId);
            
            if (!$hasPermission) {
                return $this->forbiddenResponse('Insufficient permissions');
            }
        }
        
        return $next($request);
    }
    
    /**
     * Check user permissions
     */
    private function checkUserPermissions(int $userId): bool
    {
        // Get user permissions
        $permissions = UserPermission::where('user_id', $userId)->get();
        
        if (empty($permissions)) {
            return false;
        }
        
        // Check if user has super admin role
        foreach ($permissions as $perm) {
            if ($perm->role === UserPermission::ROLE_SUPER_ADMIN) {
                return true;
            }
        }
        
        // Check specific permissions
        foreach ($permissions as $perm) {
            $hasAllRequired = true;
            
            foreach ($this->requiredPermissions as $required) {
                if (!$perm->can($required)) {
                    $hasAllRequired = false;
                    break;
                }
            }
            
            if ($hasAllRequired) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Return forbidden response
     */
    private function forbiddenResponse(string $message): Response
    {
        $response = new Response();
        $response->setStatusCode(403);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'success' => false,
            'error' => [
                'code' => 'PERMISSION_DENIED',
                'message' => $message
            ]
        ]));
        
        return $response;
    }
}
