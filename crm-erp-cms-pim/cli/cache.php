#!/usr/bin/env php
<?php

/**
 * CLI Tool for Cache Management
 * 
 * Usage:
 *   php cli/cache.php clear  - Clear all cache
 *   php cli/cache.php flush  - Flush all cache
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Cache\CacheManager;

$command = $argv[1] ?? 'clear';

try {
    switch ($command) {
        case 'clear':
        case 'flush':
            echo "Clearing cache...\n";
            CacheManager::flush();
            echo "✓ Cache cleared successfully!\n";
            break;
            
        default:
            echo "Unknown command: {$command}\n";
            echo "\nAvailable commands:\n";
            echo "  clear - Clear all cache\n";
            echo "  flush - Flush all cache\n";
            exit(1);
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
