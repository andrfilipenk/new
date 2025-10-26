<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Cache\CacheManager;

/**
 * Rate Limiter Middleware
 * 
 * Throttles requests based on IP address
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @var int Maximum requests per window
     */
    protected int $maxAttempts;
    
    /**
     * @var int Time window in seconds
     */
    protected int $decaySeconds;
    
    /**
     * Constructor
     */
    public function __construct(int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }
    
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        $attempts = CacheManager::get($key, 0);
        
        if ($attempts >= $this->maxAttempts) {
            return $this->buildTooManyRequestsResponse($key);
        }
        
        // Increment attempts
        CacheManager::put($key, $attempts + 1, $this->decaySeconds);
        
        $response = $next($request);
        
        // Add rate limit headers
        return $this->addHeaders($response, $attempts + 1);
    }
    
    /**
     * Resolve request signature
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->path();
        
        return "rate_limit:{$ip}:{$route}";
    }
    
    /**
     * Build too many requests response
     */
    protected function buildTooManyRequestsResponse(string $key): Response
    {
        $retryAfter = CacheManager::get($key . ':retry_after', $this->decaySeconds);
        
        $response = Response::json([
            'error' => 'Too Many Requests',
            'message' => 'You have exceeded the rate limit.',
            'retry_after' => $retryAfter
        ], 429);
        
        $response->setHeader('Retry-After', (string) $retryAfter);
        
        return $response;
    }
    
    /**
     * Add rate limit headers
     */
    protected function addHeaders(Response $response, int $attempts): Response
    {
        $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts - $attempts));
        
        return $response;
    }
}
