<?php

namespace App\Eav\Console;

use App\Eav\Schema\Backup\BackupManager;

/**
 * Backup List CLI Command
 * 
 * Usage: php cli.php eav:backup:list [entity-type]
 */
class BackupListCommand
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

        try {
            $backups = $this->backupManager->listBackups($entityType);

            echo "\n=== Available Backups ===\n\n";

            if (empty($backups)) {
                echo "No backups found.\n\n";
                return 0;
            }

            if ($entityType) {
                echo "Entity Type: $entityType\n\n";
            }

            echo sprintf(
                "%-20s | %-10s | %-20s | %-15s\n",
                "Entity Type",
                "Type",
                "Timestamp",
                "Size"
            );
            echo str_repeat('-', 80) . "\n";

            foreach ($backups as $backup) {
                echo sprintf(
                    "%-20s | %-10s | %-20s | %-15s\n",
                    $backup['entity_type_code'],
                    $backup['type'],
                    $backup['timestamp'],
                    $this->formatBytes($backup['file_size'])
                );
            }

            echo "\nTotal: " . count($backups) . " backup(s)\n\n";

            return 0;

        } catch (\Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            return 1;
        }
    }

    /**
     * Format bytes
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 2) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 2) . ' MB';
        return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    /**
     * Get command help
     */
    public static function getHelp(): string
    {
        return <<<HELP
Usage: php cli.php eav:backup:list [entity-type]

List available backups.

Arguments:
  entity-type         Filter by entity type (optional)

Examples:
  php cli.php eav:backup:list
  php cli.php eav:backup:list customer

HELP;
    }
}
