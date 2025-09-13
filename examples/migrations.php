<?php
$migrator = $container->get(\Core\Database\Migrator::class);
$migrator->run();

// config/app.php
return [
    // ... other config
    'migrations' => [
        'path' => APP_PATH . '/migrations',
        'table' => 'migrations'
    ]
];

/**
 * 
 */


use Core\Di\Injectable;

/**
 * Migration console commands
 */
class MigrationCommand
{
    use Injectable;

    protected $migrator;

    public function __construct()
    {
        $this->migrator = $this->container->get(\Core\Database\Migrator::class);
    }

    public function migrate()
    {
        $migrations = $this->migrator->run();
        
        if (empty($migrations)) {
            echo "No migrations to run.\n";
            return;
        }
        
        echo "Ran migrations:\n";
        foreach ($migrations as $migration) {
            echo "- {$migration}\n";
        }
    }

    public function rollback()
    {
        $migrations = $this->migrator->rollback();
        
        if (empty($migrations)) {
            echo "No migrations to rollback.\n";
            return;
        }
        
        echo "Rolled back migrations:\n";
        foreach ($migrations as $migration) {
            echo "- {$migration}\n";
        }
    }

    public function reset()
    {
        $migrations = $this->migrator->reset();
        
        if (empty($migrations)) {
            echo "No migrations to reset.\n";
            return;
        }
        
        echo "Reset migrations:\n";
        foreach ($migrations as $migration) {
            echo "- {$migration}\n";
        }
    }
}