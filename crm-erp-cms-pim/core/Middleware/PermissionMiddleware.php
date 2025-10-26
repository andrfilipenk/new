<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Auth\Auth;

/**
 * Permission Middleware
 * 
 * Check if user has specific permission
 */
class PermissionMiddleware implements MiddlewareInterface
{
    protected string $permission;
    
    public function __construct(string $permission = '')
    {
        $this->permission = $permission;
    }
    
    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return Response::json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in.'
            ], 401);
        }
        
        if (!$user->hasPermission($this->permission)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }
        
        return $next($request);
    }
}
