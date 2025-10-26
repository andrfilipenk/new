<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Auth\Auth;

/**
 * Auth Middleware
 * 
 * Ensures user is authenticated
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::guest()) {
            // Store intended URL for redirect after login
            if (!$request->expectsJson() && !$request->isAjax()) {
                $intendedUrl = $request->getUri();
                if ($intendedUrl !== '/' && $intendedUrl !== '/login' && $intendedUrl !== '/register') {
                    \Core\Session\SessionManager::set('intended_url', $intendedUrl);
                }
            }
            
            // Redirect to login if web request
            if (!$request->expectsJson() || !$request->isAjax()) {
                return Response::redirect('/login');
            }
            
            // Return JSON error for API requests
            return Response::json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource.'
            ], 401);
        }
        
        return $next($request);
    }
}
