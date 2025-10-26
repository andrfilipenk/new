#!/usr/bin/env php
<?php

/**
 * CLI Tool for Database Migrations
 * 
 * Usage:
 *   php cli/migrate.php          - Run pending migrations
 *   php cli/migrate.php rollback - Rollback last migration batch
 *   php cli/migrate.php reset    - Rollback all migrations
 *   php cli/migrate.php refresh  - Reset and re-run all migrations
 *   php cli/migrate.php status   - Show migration status
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Database\Migrator;

$command = $argv[1] ?? 'migrate';

$migrator = new Migrator();

try {
    switch ($command) {
        case 'migrate':
        case 'run':
            echo "Running migrations...\n";
            $ran = $migrator->run();
            
            if (empty($ran)) {
                echo "Nothing to migrate.\n";
            } else {
                echo "Migrated:\n";
                foreach ($ran as $migration) {
                    echo "  ✓ {$migration}\n";
                }
                echo "\nMigrations completed successfully!\n";
            }
            break;
            
        case 'rollback':
            echo "Rolling back migrations...\n";
            $steps = isset($argv[2]) ? (int)$argv[2] : 1;
            $rolledBack = $migrator->rollback($steps);
            
            if (empty($rolledBack)) {
                echo "Nothing to rollback.\n";
            } else {
                echo "Rolled back:\n";
                foreach ($rolledBack as $migration) {
                    echo "  ✓ {$migration}\n";
                }
                echo "\nRollback completed successfully!\n";
            }
            break;
            
        case 'reset':
            echo "Resetting all migrations...\n";
            $rolledBack = $migrator->reset();
            
            echo "Reset:\n";
            foreach ($rolledBack as $migration) {
                echo "  ✓ {$migration}\n";
            }
            echo "\nReset completed successfully!\n";
            break;
            
        case 'refresh':
            echo "Refreshing migrations...\n";
            $ran = $migrator->refresh();
            
            echo "Refreshed:\n";
            foreach ($ran as $migration) {
                echo "  ✓ {$migration}\n";
            }
            echo "\nRefresh completed successfully!\n";
            break;
            
        case 'status':
            $ran = $migrator->getRan();
            $pending = $migrator->getPending();
            
            echo "Migration Status:\n";
            echo "================\n\n";
            
            if (!empty($ran)) {
                echo "Ran migrations:\n";
                foreach ($ran as $migration) {
                    echo "  ✓ {$migration}\n";
                }
                echo "\n";
            }
            
            if (!empty($pending)) {
                echo "Pending migrations:\n";
                foreach ($pending as $migration) {
                    echo "  • {$migration}\n";
                }
                echo "\n";
            }
            
            if (empty($ran) && empty($pending)) {
                echo "No migrations found.\n";
            }
            break;
            
        default:
            echo "Unknown command: {$command}\n";
            echo "\nAvailable commands:\n";
            echo "  migrate  - Run pending migrations\n";
            echo "  rollback - Rollback last migration batch\n";
            echo "  reset    - Rollback all migrations\n";
            echo "  refresh  - Reset and re-run all migrations\n";
            echo "  status   - Show migration status\n";
            exit(1);
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
