<?php

namespace Eav\Admin\Middleware;

use Core\Http\Request;
use Core\Http\Response;

class RateLimitMiddleware
{
    private array $limits = [
        'read' => ['limit' => 300, 'window' => 60],        // 300 per minute
        'write' => ['limit' => 100, 'window' => 60],       // 100 per minute
        'bulk' => ['limit' => 10, 'window' => 60],         // 10 per minute
        'schema' => ['limit' => 5, 'window' => 60],        // 5 per minute
        'export' => ['limit' => 20, 'window' => 60]        // 20 per minute
    ];
    
    private string $category;
    private string $storageDriver = 'file'; // 'file' or 'redis'
    private string $storagePath;
    
    public function __construct(string $category = 'read', array $config = [])
    {
        $this->category = $category;
        
        if (isset($config['limits'])) {
            $this->limits = array_merge($this->limits, $config['limits']);
        }
        
        $this->storageDriver = $config['storage_driver'] ?? 'file';
        $this->storagePath = $config['storage_path'] ?? sys_get_temp_dir() . '/eav_rate_limits';
        
        if ($this->storageDriver === 'file' && !is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    /**
     * Handle rate limiting
     */
    public function handle(Request $request, callable $next)
    {
        $identifier = $this->getIdentifier($request);
        
        $limit = $this->limits[$this->category]['limit'] ?? 100;
        $window = $this->limits[$this->category]['window'] ?? 60;
        
        // Check rate limit
        $current = $this->getCurrentCount($identifier);
        
        if ($current >= $limit) {
            return $this->rateLimitExceededResponse($limit, $window);
        }
        
        // Increment counter
        $this->incrementCount($identifier, $window);
        
        // Add rate limit headers to response
        $response = $next($request);
        
        if ($response instanceof Response) {
            $remaining = max(0, $limit - $current - 1);
            $resetTime = time() + $window;
            
            $response->setHeader('X-RateLimit-Limit', (string)$limit);
            $response->setHeader('X-RateLimit-Remaining', (string)$remaining);
            $response->setHeader('X-RateLimit-Reset', (string)$resetTime);
        }
        
        return $response;
    }
    
    /**
     * Get identifier for rate limiting (user ID or IP)
     */
    private function getIdentifier(Request $request): string
    {
        // Use user ID if authenticated
        $userId = $request->getAttribute('user_id');
        
        if ($userId) {
            return "user_{$userId}";
        }
        
        // Fall back to IP address
        $ip = $this->getClientIp($request);
        
        return "ip_{$ip}";
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        if ($request->header('X-Forwarded-For')) {
            $ip = explode(',', $request->header('X-Forwarded-For'))[0];
        } elseif ($request->header('X-Real-IP')) {
            $ip = $request->header('X-Real-IP');
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return trim($ip);
    }
    
    /**
     * Get current request count
     */
    private function getCurrentCount(string $identifier): int
    {
        if ($this->storageDriver === 'redis') {
            return $this->getCountRedis($identifier);
        }
        
        return $this->getCountFile($identifier);
    }
    
    /**
     * Increment request count
     */
    private function incrementCount(string $identifier, int $window): void
    {
        if ($this->storageDriver === 'redis') {
            $this->incrementCountRedis($identifier, $window);
        } else {
            $this->incrementCountFile($identifier, $window);
        }
    }
    
    /**
     * Get count from file storage
     */
    private function getCountFile(string $identifier): int
    {
        $key = $this->getStorageKey($identifier);
        $filePath = $this->storagePath . '/' . $key;
        
        if (!file_exists($filePath)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        
        if (!$data || $data['expires'] < time()) {
            unlink($filePath);
            return 0;
        }
        
        return $data['count'] ?? 0;
    }
    
    /**
     * Increment count in file storage
     */
    private function incrementCountFile(string $identifier, int $window): void
    {
        $key = $this->getStorageKey($identifier);
        $filePath = $this->storagePath . '/' . $key;
        
        $count = 1;
        $expires = time() + $window;
        
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            
            if ($data && $data['expires'] >= time()) {
                $count = $data['count'] + 1;
                $expires = $data['expires'];
            }
        }
        
        file_put_contents($filePath, json_encode([
            'count' => $count,
            'expires' => $expires
        ]));
    }
    
    /**
     * Get count from Redis
     */
    private function getCountRedis(string $identifier): int
    {
        // Placeholder for Redis implementation
        // In production, use actual Redis client
        return 0;
    }
    
    /**
     * Increment count in Redis
     */
    private function incrementCountRedis(string $identifier, int $window): void
    {
        // Placeholder for Redis implementation
        // Example:
        // $redis = new Redis();
        // $redis->connect('127.0.0.1', 6379);
        // $key = "rate_limit:{$this->category}:{$identifier}";
        // $redis->incr($key);
        // $redis->expire($key, $window);
    }
    
    /**
     * Get storage key
     */
    private function getStorageKey(string $identifier): string
    {
        return md5("{$this->category}:{$identifier}");
    }
    
    /**
     * Return rate limit exceeded response
     */
    private function rateLimitExceededResponse(int $limit, int $window): Response
    {
        $retryAfter = $window;
        
        $response = new Response();
        $response->setStatusCode(429);
        $response->setHeader('Content-Type', 'application/json');
        $response->setHeader('Retry-After', (string)$retryAfter);
        $response->setHeader('X-RateLimit-Limit', (string)$limit);
        $response->setHeader('X-RateLimit-Remaining', '0');
        $response->setHeader('X-RateLimit-Reset', (string)(time() + $retryAfter));
        $response->setContent(json_encode([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => "You have exceeded {$limit} requests per {$window} seconds",
                'retry_after' => $retryAfter
            ]
        ]));
        
        return $response;
    }
    
    /**
     * Clean up expired rate limit files
     */
    public static function cleanup(string $storagePath): int
    {
        if (!is_dir($storagePath)) {
            return 0;
        }
        
        $deleted = 0;
        $files = glob($storagePath . '/*');
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || $data['expires'] < time()) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}
