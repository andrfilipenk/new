<?php

namespace Core\Cache;

use Core\Config\Config;

/**
 * Cache Manager
 * 
 * File-based caching with TTL support
 */
class CacheManager
{
    protected static ?self $instance = null;
    protected string $cachePath;
    protected string $prefix;

    private function __construct()
    {
        $this->cachePath = Config::get('app.storage_path', BASE_PATH . '/storage') . '/cache';
        $this->prefix = Config::get('cache.prefix', 'framework_cache');
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get cached value
     */
    public static function get(string $key, $default = null)
    {
        return self::getInstance()->retrieve($key, $default);
    }

    /**
     * Store value in cache
     */
    public static function put(string $key, $value, int $ttl = 3600): bool
    {
        return self::getInstance()->store($key, $value, $ttl);
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        return self::getInstance()->exists($key);
    }

    /**
     * Remove cached value
     */
    public static function forget(string $key): bool
    {
        return self::getInstance()->remove($key);
    }

    /**
     * Clear all cache
     */
    public static function flush(): bool
    {
        return self::getInstance()->clear();
    }

    /**
     * Remember value (get or store)
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $instance = self::getInstance();
        
        if ($instance->exists($key)) {
            return $instance->retrieve($key);
        }

        $value = $callback();
        $instance->store($key, $value, $ttl);
        
        return $value;
    }

    protected function retrieve(string $key, $default = null)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }

        $data = unserialize(file_get_contents($filename));
        
        if ($data['expires_at'] < time()) {
            $this->remove($key);
            return $default;
        }

        return $data['value'];
    }

    protected function store(string $key, $value, int $ttl): bool
    {
        $filename = $this->getFilename($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];

        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }

    protected function exists(string $key): bool
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }

        $data = unserialize(file_get_contents($filename));
        
        if ($data['expires_at'] < time()) {
            $this->remove($key);
            return false;
        }

        return true;
    }

    protected function remove(string $key): bool
    {
        $filename = $this->getFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    protected function clear(): bool
    {
        $files = glob($this->cachePath . '/' . $this->prefix . '_*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    protected function getFilename(string $key): string
    {
        return $this->cachePath . '/' . $this->prefix . '_' . md5($key);
    }
}
