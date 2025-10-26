<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Auth\Auth;

/**
 * Guest Middleware
 * 
 * Ensures user is NOT authenticated
 */
class GuestMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            // Redirect to dashboard if already logged in
            return Response::redirect('/dashboard');
        }
        
        return $next($request);
    }
}
