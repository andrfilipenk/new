#!/usr/bin/env php
<?php

/**
 * CLI Tool for Database Seeding
 * 
 * Usage:
 *   php cli/seed.php
 */

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../database/seeds/DatabaseSeeder.php';

try {
    echo "\n";
    echo "╔════════════════════════════════════════╗\n";
    echo "║     Database Seeding Started           ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    
    $seeder = new DatabaseSeeder();
    $seeder->run();
    
    echo "\n";
    echo "╔════════════════════════════════════════╗\n";
    echo "║   ✓ Seeding Completed Successfully!    ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    exit(1);
}
