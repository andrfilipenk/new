<?php

namespace Eav\Admin\Middleware;

use Eav\Admin\Models\ApiToken;
use Core\Http\Request;
use Core\Http\Response;

class ApiAuthenticationMiddleware
{
    /**
     * Handle authentication for API requests
     */
    public function handle(Request $request, callable $next)
    {
        // Check for API token in Authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return $this->unauthorizedResponse('Missing Authorization header');
        }
        
        // Extract token (Bearer token format)
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid Authorization header format');
        }
        
        $token = $matches[1];
        
        // Verify token
        $apiToken = ApiToken::verify($token);
        
        if (!$apiToken) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }
        
        // Mark token as used
        $apiToken->markAsUsed();
        
        // Attach user and token to request
        $request->setAttribute('api_token', $apiToken);
        $request->setAttribute('user_id', $apiToken->user_id);
        
        // Continue to next middleware/controller
        return $next($request);
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        $response = new Response();
        $response->setStatusCode(401);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'success' => false,
            'error' => [
                'code' => 'AUTHENTICATION_REQUIRED',
                'message' => $message
            ]
        ]));
        
        return $response;
    }
}
