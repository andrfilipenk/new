<?php

namespace App\Eav\Console;

use App\Eav\Schema\Backup\BackupManager;
use App\Eav\Schema\Backup\BackupType;

/**
 * Backup Create CLI Command
 * 
 * Usage: php cli.php eav:backup:create <entity-type> [--type=full]
 */
class BackupCreateCommand
{
    private BackupManager $backupManager;

    public function __construct(BackupManager $backupManager)
    {
        $this->backupManager = $backupManager;
    }

    /**
     * Execute command
     */
    public function execute(array $args = []): int
    {
        $entityType = $args['entity-type'] ?? null;
        
        if (!$entityType) {
            echo "Error: Entity type is required\n";
            echo self::getHelp();
            return 1;
        }

        $type = $args['--type'] ?? 'full';

        // Validate backup type
        if (!in_array($type, ['schema', 'data', 'full'])) {
            echo "Error: Invalid backup type. Must be: schema, data, or full\n";
            return 1;
        }

        try {
            echo "\n=== Creating Backup ===\n\n";
            echo "Entity Type: $entityType\n";
            echo "Backup Type: $type\n";
            echo "Creating backup...\n\n";

            $startTime = microtime(true);
            $backup = $this->backupManager->createBackup($entityType, $type);
            $duration = microtime(true) - $startTime;

            echo "✓ Backup created successfully\n\n";
            echo "--- Backup Details ---\n";
            echo "Backup ID: {$backup->getId()}\n";
            echo "Storage Path: {$backup->getStoragePath()}\n";
            echo "File Size: " . $this->formatBytes($backup->getFileSize()) . "\n";
            echo "Created At: {$backup->getCreatedAt()->format('Y-m-d H:i:s')}\n";
            echo "Duration: " . number_format($duration, 2) . "s\n\n";

            return 0;

        } catch (\Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            return 1;
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }

    /**
     * Get command help
     */
    public static function getHelp(): string
    {
        return <<<HELP
Usage: php cli.php eav:backup:create <entity-type> [options]

Create a backup of entity type schema and/or data.

Arguments:
  entity-type         Entity type code to backup (required)

Options:
  --type=TYPE        Backup type: schema|data|full (default: full)

Backup Types:
  schema             Schema DDL only (~10KB, <1s)
  data               Data only (varies by volume)
  full               Schema + Data (most comprehensive)

Examples:
  php cli.php eav:backup:create customer
  php cli.php eav:backup:create customer --type=schema
  php cli.php eav:backup:create product --type=full

HELP;
    }
}
