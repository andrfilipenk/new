<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Auth\Auth;

/**
 * Role Middleware
 * 
 * Check if user has specific role
 */
class RoleMiddleware implements MiddlewareInterface
{
    protected string $role;
    
    public function __construct(string $role = '')
    {
        $this->role = $role;
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
        
        if (!$user->hasRole($this->role)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => 'You do not have the required role to access this resource.'
            ], 403);
        }
        
        return $next($request);
    }
}
