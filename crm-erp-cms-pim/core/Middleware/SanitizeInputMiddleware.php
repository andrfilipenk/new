<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Security\SecurityManager;

/**
 * Sanitize Input Middleware
 * 
 * Sanitizes all input data to prevent XSS attacks
 */
class SanitizeInputMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Sanitize GET data
        $_GET = $this->sanitizeArray($_GET);
        
        // Sanitize POST data
        $_POST = $this->sanitizeArray($_POST);
        
        // Sanitize REQUEST data
        $_REQUEST = $this->sanitizeArray($_REQUEST);
        
        return $next($request);
    }
    
    /**
     * Sanitize array recursively
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = SecurityManager::sanitizeInput($value);
            }
        }
        
        return $sanitized;
    }
}
