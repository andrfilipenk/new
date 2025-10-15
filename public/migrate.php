<?php
// filepath: c:\xampp\htdocs\new\migrate.php

require '../bootstrap.php';

$di = \Core\Di\Container::getDefault();
/** @var \Core\Database\Migrator $migrator */
$migrator = $di->get('migrator');

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        echo "Running migrations...\n";
        $executed = $migrator->run();
        if (empty($executed)) {
            echo "Nothing to migrate.\n";
        } else {
            foreach ($executed as $migration) {
                echo "Migrated: {$migration}\n";
            }
        }
        break;

    case 'rollback':
        echo "Rolling back last batch...\n";
        $rolledBack = $migrator->rollback();
        if (empty($rolledBack)) {
            echo "Nothing to roll back.\n";
        } else {
            foreach ($rolledBack as $migration) {
                echo "Rolled back: {$migration}\n";
            }
        }
        break;

    case 'reset':
        echo "Resetting all migrations...\n";
        $rolledBack = $migrator->reset();
        foreach ($rolledBack as $migration) {
            echo "Rolled back: {$migration}\n";
        }
        echo "All migrations have been reset.\n";
        break;

    default:
        echo "Migration Tool\n";
        echo "Usage: php migrate.php [command]\n\n";
        echo "Commands:\n";
        echo "  migrate   - Run all outstanding migrations\n";
        echo "  rollback  - Roll back the last batch of migrations\n";
        echo "  reset     - Roll back all migrations\n";
        break;
}



/*
php migrate.php migrate
php migrate.php rollback
Rolling back last batch...
Rolled back: 2025_09_16_100000_create_posts_table.php
Rolled back: 2023_10_15_123456_create_users_table.php

*/