<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Security\SecurityManager;

/**
 * CSRF Middleware
 * 
 * Verifies CSRF tokens for state-changing requests
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF check for GET, HEAD, OPTIONS
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }
        
        // Get token from request
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        
        // Validate token
        if (!$token || !SecurityManager::validateCsrfToken($token)) {
            return Response::json([
                'error' => 'CSRF token mismatch',
                'message' => 'The request could not be authenticated.'
            ], 419);
        }
        
        return $next($request);
    }
}
