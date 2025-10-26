<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\RateLimit\RateLimiter;

/**
 * Throttle Middleware
 * 
 * Rate limits requests based on IP address or user
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;
    
    /**
     * Constructor
     */
    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }
    
    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestKey($request);
        
        $limiter = new RateLimiter($this->maxAttempts, $this->decayMinutes * 60);
        
        if ($limiter->tooManyAttempts($key)) {
            return $this->buildTooManyAttemptsResponse($limiter, $key);
        }
        
        $limiter->hit($key);
        
        $response = $next($request);
        
        return $this->addHeaders($response, $limiter, $key);
    }
    
    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(Request $request): string
    {
        // Use user ID if authenticated, otherwise use IP
        $userId = auth()->id();
        
        if ($userId) {
            return RateLimiter::forUser($userId) . ':' . $request->path();
        }
        
        return RateLimiter::forRoute($request->path());
    }
    
    /**
     * Build too many attempts response
     */
    protected function buildTooManyAttemptsResponse(RateLimiter $limiter, string $key): Response
    {
        $retryAfter = $limiter->availableIn($key);
        
        $headers = [
            'X-RateLimit-Limit' => $this->maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ];
        
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
        
        if ($isApi) {
            $response = Response::json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter
            ])->setHeaders($headers);
            return $response;
        }
        
        return Response::html(
            '<h1>429 Too Many Requests</h1><p>Please try again in ' . $retryAfter . ' seconds.</p>',
            429
        )->setHeaders($headers);
    }
    
    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, RateLimiter $limiter, string $key): Response
    {
        $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) $limiter->remaining($key));
        $response->setHeader('X-RateLimit-Reset', (string) (time() + $limiter->availableIn($key)));
        
        return $response;
    }
}
