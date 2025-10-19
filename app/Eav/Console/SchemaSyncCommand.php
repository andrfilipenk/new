<?php

namespace App\Eav\Console;

use App\Eav\Schema\Sync\SynchronizationEngine;
use App\Eav\Schema\Sync\SyncOptions;

/**
 * Schema Sync CLI Command
 * 
 * Usage: php cli.php eav:schema:sync [entity-type] [--strategy=additive] [--dry-run] [--force]
 */
class SchemaSyncCommand
{
    private SynchronizationEngine $syncEngine;

    public function __construct(SynchronizationEngine $syncEngine)
    {
        $this->syncEngine = $syncEngine;
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

        $strategy = $args['--strategy'] ?? 'additive';
        $dryRun = isset($args['--dry-run']);
        $force = isset($args['--force']);
        $noBackup = isset($args['--no-backup']);

        // Create sync options
        $options = new SyncOptions(
            strategy: $strategy,
            dryRun: $dryRun,
            autoBackup: !$noBackup,
            force: $force
        );

        try {
            echo "\n=== Schema Synchronization ===\n\n";
            echo "Entity Type: $entityType\n";
            echo "Strategy: $strategy\n";
            echo "Dry Run: " . ($dryRun ? 'Yes' : 'No') . "\n";
            echo "Auto Backup: " . (!$noBackup ? 'Yes' : 'No') . "\n";
            echo "Force: " . ($force ? 'Yes' : 'No') . "\n\n";

            if (!$dryRun && !$force) {
                echo "Warning: This will modify the database schema.\n";
                echo "Use --dry-run to preview changes first, or --force to proceed.\n\n";
                return 1;
            }

            $result = $this->syncEngine->sync($entityType, $options);

            $this->displayResult($result);

            return $result->isSuccess() ? 0 : 1;

        } catch (\Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            return 1;
        }
    }

    /**
     * Display sync result
     */
    private function displayResult($result): void
    {
        echo "\n--- Sync Result ---\n\n";
        echo "Status: {$result->getStatus()}\n";
        echo "Success: " . ($result->isSuccess() ? 'Yes' : 'No') . "\n";
        echo "Execution Time: " . number_format($result->getExecutionTime(), 3) . "s\n";

        if ($result->getBackupId()) {
            echo "Backup Created: #{$result->getBackupId()}\n";
        }

        if (!empty($result->getAppliedChanges())) {
            echo "\nApplied Changes:\n";
            foreach ($result->getAppliedChanges() as $change) {
                echo "  ✓ {$change['description']}\n";
            }
        }

        $metadata = $result->getMetadata();
        if (isset($metadata['planned_changes'])) {
            echo "\nPlanned Changes (Dry Run):\n";
            foreach ($metadata['planned_changes'] as $change) {
                echo "  • $change\n";
            }
        }

        if ($result->hasErrors()) {
            echo "\nErrors:\n";
            foreach ($result->getErrors() as $error) {
                echo "  ✗ {$error['message']}\n";
                if (isset($error['details'])) {
                    echo "    Details: {$error['details']}\n";
                }
            }
        }

        echo "\n";
    }

    /**
     * Get command help
     */
    public static function getHelp(): string
    {
        return <<<HELP
Usage: php cli.php eav:schema:sync <entity-type> [options]

Synchronize database schema with configuration.

Arguments:
  entity-type         Entity type code to synchronize (required)

Options:
  --strategy=TYPE    Sync strategy: additive|full (default: additive)
  --dry-run          Preview changes without applying
  --force            Skip confirmation prompts
  --no-backup        Skip automatic backup

Strategies:
  additive          Only add new structures (safe, recommended)
  full              Add, modify, and remove structures (use with caution)

Examples:
  php cli.php eav:schema:sync customer --dry-run
  php cli.php eav:schema:sync customer --strategy=additive --force
  php cli.php eav:schema:sync product --strategy=full --force

HELP;
    }
}
