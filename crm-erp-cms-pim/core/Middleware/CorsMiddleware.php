<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->method() === 'OPTIONS') {
            return $this->preflightResponse();
        }
        
        // Process request
        $response = $next($request);
        
        // Add CORS headers to response
        return $this->addCorsHeaders($response);
    }
    
    /**
     * Create preflight response
     */
    protected function preflightResponse(): Response
    {
        $response = Response::json(['status' => 'ok'], 200);
        return $this->addCorsHeaders($response);
    }
    
    /**
     * Add CORS headers
     */
    protected function addCorsHeaders(Response $response): Response
    {
        $allowedOrigins = config('cors.allowed_origins', ['*']);
        $allowedMethods = config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);
        $allowedHeaders = config('cors.allowed_headers', ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN']);
        $maxAge = config('cors.max_age', 86400);
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Check if origin is allowed
        if ($allowedOrigins !== ['*'] && !in_array($origin, $allowedOrigins)) {
            $origin = $allowedOrigins[0] ?? '*';
        }
        
        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
        $response->setHeader('Access-Control-Max-Age', (string) $maxAge);
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}
