<?php

namespace App\Core\Eav\Cache\Driver;

/**
 * File-based Cache Driver
 * 
 * Provides persistent file-based caching with TTL support.
 * - Stores cache as serialized PHP files
 * - Survives process restarts
 * - Automatic expiry cleanup
 * - No external dependencies
 * 
 * @package App\Core\Eav\Cache\Driver
 */
class FileDriver implements CacheDriverInterface
{
    private string $cacheDir;
    private string $prefix;
    private int $dirPermissions;
    private int $filePermissions;

    /**
     * @param string $cacheDir Directory to store cache files
     * @param string $prefix Key prefix for namespacing
     * @param int $dirPermissions Directory permissions (octal)
     * @param int $filePermissions File permissions (octal)
     */
    public function __construct(
        string $cacheDir = '/tmp/eav_cache',
        string $prefix = 'eav_l3_',
        int $dirPermissions = 0755,
        int $filePermissions = 0644
    ) {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->prefix = $prefix;
        $this->dirPermissions = $dirPermissions;
        $this->filePermissions = $filePermissions;
        
        $this->ensureCacheDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): mixed
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $data = @unserialize(file_get_contents($filePath));
        
        if ($data === false || !is_array($data)) {
            // Corrupted cache file
            @unlink($filePath);
            return null;
        }
        
        // Check expiry
        if (isset($data['expiry']) && $data['expiry'] < time()) {
            @unlink($filePath);
            return null;
        }
        
        return $data['value'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $filePath = $this->getFilePath($key);
        $expiry = time() + $ttl;
        
        $data = [
            'value' => $value,
            'expiry' => $expiry,
            'created' => time(),
        ];
        
        $serialized = serialize($data);
        
        // Write atomically using temp file + rename
        $tempFile = $filePath . '.tmp.' . uniqid();
        
        if (@file_put_contents($tempFile, $serialized, LOCK_EX) === false) {
            return false;
        }
        
        @chmod($tempFile, $this->filePermissions);
        
        if (!@rename($tempFile, $filePath)) {
            @unlink($tempFile);
            return false;
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return true;
        }
        
        return @unlink($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }
        
        $files = glob($this->cacheDir . '/' . $this->prefix . '*');
        
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!@unlink($file)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return is_dir($this->cacheDir) && is_writable($this->cacheDir);
    }

    /**
     * Clean expired cache files
     * 
     * @return int Number of files cleaned
     */
    public function cleanExpired(): int
    {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }
        
        $files = glob($this->cacheDir . '/' . $this->prefix . '*');
        
        if ($files === false) {
            return 0;
        }
        
        $cleaned = 0;
        $now = time();
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $data = @unserialize(file_get_contents($file));
            
            if ($data === false || !is_array($data)) {
                // Corrupted file
                @unlink($file);
                $cleaned++;
                continue;
            }
            
            if (isset($data['expiry']) && $data['expiry'] < $now) {
                @unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    /**
     * Get cache statistics
     * 
     * @return array Statistics about cache files
     */
    public function getInfo(): array
    {
        if (!is_dir($this->cacheDir)) {
            return [
                'num_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
            ];
        }
        
        $files = glob($this->cacheDir . '/' . $this->prefix . '*');
        
        if ($files === false) {
            return [
                'num_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
            ];
        }
        
        $totalSize = 0;
        $expiredFiles = 0;
        $now = time();
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $totalSize += filesize($file);
            
            $data = @unserialize(file_get_contents($file));
            if (is_array($data) && isset($data['expiry']) && $data['expiry'] < $now) {
                $expiredFiles++;
            }
        }
        
        return [
            'num_files' => count($files),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'expired_files' => $expiredFiles,
            'cache_dir' => $this->cacheDir,
            'writable' => is_writable($this->cacheDir),
        ];
    }

    /**
     * Ensure cache directory exists with correct permissions
     */
    private function ensureCacheDirectory(): void
    {
        if (is_dir($this->cacheDir)) {
            return;
        }
        
        @mkdir($this->cacheDir, $this->dirPermissions, true);
    }

    /**
     * Get file path for cache key
     */
    private function getFilePath(string $key): string
    {
        $safeKey = $this->prefix . md5($key);
        return $this->cacheDir . '/' . $safeKey;
    }

    /**
     * Set cache directory
     */
    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->ensureCacheDirectory();
    }

    /**
     * Get cache directory
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }
}
