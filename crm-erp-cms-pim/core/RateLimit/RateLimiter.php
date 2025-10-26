<?php

namespace Core\RateLimit;

use Core\Cache\CacheManager;

/**
 * Rate Limiter
 * 
 * Throttles requests based on configurable limits
 */
class RateLimiter
{
    private CacheManager $cache;
    private int $maxAttempts;
    private int $decaySeconds;
    
    /**
     * Constructor
     */
    public function __construct(?int $maxAttempts = null, ?int $decaySeconds = null)
    {
        $this->cache = CacheManager::getInstance();
        $this->maxAttempts = $maxAttempts ?? 60;
        $this->decaySeconds = $decaySeconds ?? 60;
    }
    
    /**
     * Check if key has too many attempts
     */
    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }
    
    /**
     * Increment attempts for the key
     */
    public function hit(string $key, $decaySeconds = null): int
    {
        $decaySeconds = $decaySeconds ?? $this->decaySeconds;
        
        $cacheKey = $this->getCacheKey($key);
        
        // Get current attempts
        $attempts = (int) $this->cache->get($cacheKey, 0);
        
        // Increment
        $attempts++;
        
        // Store with TTL
        $this->cache->put($cacheKey, $attempts, $decaySeconds);
        
        // Store timer key to track when the limit started
        $timerKey = $cacheKey . ':timer';
        if (!$this->cache->has($timerKey)) {
            $this->cache->put($timerKey, time(), $decaySeconds);
        }
        
        return $attempts;
    }
    
    /**
     * Get the number of attempts
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($this->getCacheKey($key), 0);
    }
    
    /**
     * Reset attempts for the key
     */
    public function resetAttempts(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        $this->cache->forget($cacheKey);
        $this->cache->forget($cacheKey . ':timer');
    }
    
    /**
     * Get number of remaining attempts
     */
    public function remaining(string $key): int
    {
        $attempts = $this->attempts($key);
        return max(0, $this->maxAttempts - $attempts);
    }
    
    /**
     * Get number of seconds until rate limit resets
     */
    public function availableIn(string $key): int
    {
        $timerKey = $this->getCacheKey($key) . ':timer';
        $startTime = (int) $this->cache->get($timerKey, time());
        
        $endTime = $startTime + $this->decaySeconds;
        $remaining = $endTime - time();
        
        return max(0, $remaining);
    }
    
    /**
     * Clear all rate limit data for a key
     */
    public function clear(string $key): void
    {
        $this->resetAttempts($key);
    }
    
    /**
     * Execute callback unless rate limited
     */
    public function attempt(string $key, callable $callback, ?int $maxAttempts = null, ?int $decaySeconds = null)
    {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $oldMaxAttempts = $this->maxAttempts;
        $this->maxAttempts = $maxAttempts;
        
        if ($decaySeconds) {
            $oldDecaySeconds = $this->decaySeconds;
            $this->decaySeconds = $decaySeconds;
        }
        
        if ($this->tooManyAttempts($key)) {
            if (isset($oldDecaySeconds)) {
                $this->decaySeconds = $oldDecaySeconds;
            }
            $this->maxAttempts = $oldMaxAttempts;
            return false;
        }
        
        $result = $callback();
        
        if ($result !== false) {
            $this->hit($key, $decaySeconds);
        }
        
        if (isset($oldDecaySeconds)) {
            $this->decaySeconds = $oldDecaySeconds;
        }
        $this->maxAttempts = $oldMaxAttempts;
        
        return $result;
    }
    
    /**
     * Get cache key for rate limiting
     */
    private function getCacheKey(string $key): string
    {
        return 'rate_limit:' . $key;
    }
    
    /**
     * Create a rate limiter for API requests
     */
    public static function for(string $name, int $maxAttempts = 60, int $decaySeconds = 60): self
    {
        return new self($maxAttempts, $decaySeconds);
    }
    
    /**
     * Create a rate limiter by IP address
     */
    public static function forIp(?string $ip = null): string
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return 'ip:' . $ip;
    }
    
    /**
     * Create a rate limiter by user ID
     */
    public static function forUser(int $userId): string
    {
        return 'user:' . $userId;
    }
    
    /**
     * Create a rate limiter for login attempts
     */
    public static function forLogin(string $email): string
    {
        return 'login:' . sha1(strtolower($email));
    }
    
    /**
     * Create a rate limiter for API endpoint
     */
    public static function forRoute(string $route, ?string $ip = null): string
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return 'route:' . $route . ':' . $ip;
    }
}
