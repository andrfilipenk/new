<?php

namespace App\Eav\Schema\Sync;

use App\Eav\Schema\Analysis\SchemaAnalyzer;
use App\Eav\Schema\Comparison\SchemaComparator;
use App\Eav\Schema\Migration\MigrationGenerator;
use App\Eav\Schema\Migration\MigrationExecutor;
use App\Eav\Schema\Backup\BackupManager;
use App\Eav\Schema\DifferenceSet;
use App\Eav\Schema\SchemaDifference;
use App\Eav\Config\EntityTypeRegistry;
use Core\Database\Connection;
use Core\Events\EventDispatcher;

/**
 * Synchronization Engine
 * 
 * Orchestrates schema updates to align database with configuration.
 */
class SynchronizationEngine
{
    private SchemaAnalyzer $analyzer;
    private SchemaComparator $comparator;
    private MigrationGenerator $generator;
    private MigrationExecutor $executor;
    private BackupManager $backupManager;
    private EntityTypeRegistry $registry;
    private Connection $db;
    private ?EventDispatcher $eventDispatcher;

    public function __construct(
        SchemaAnalyzer $analyzer,
        SchemaComparator $comparator,
        MigrationGenerator $generator,
        MigrationExecutor $executor,
        BackupManager $backupManager,
        EntityTypeRegistry $registry,
        Connection $db,
        ?EventDispatcher $eventDispatcher = null
    ) {
        $this->analyzer = $analyzer;
        $this->comparator = $comparator;
        $this->generator = $generator;
        $this->executor = $executor;
        $this->backupManager = $backupManager;
        $this->registry = $registry;
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Synchronize schema for entity type
     */
    public function sync(string $entityTypeCode, SyncOptions $options): SyncResult
    {
        $startTime = microtime(true);
        $result = new SyncResult($entityTypeCode);

        try {
            $this->dispatchEvent('schema.sync.started', ['entity_type_code' => $entityTypeCode]);

            // Step 1: Analyze schema
            $report = $this->analyzer->analyze($entityTypeCode);
            
            if (!$report->hasDifferences()) {
                $result->setStatus('in_sync');
                $result->addMetadata('message', 'Schema is already in sync');
                $this->dispatchEvent('schema.sync.completed', ['result' => $result]);
                return $result;
            }

            // Step 2: Validate changes
            if (!$options->shouldSkipValidation()) {
                $this->validateChanges($report, $options, $result);
                if ($result->hasErrors()) {
                    $result->setSuccess(false);
                    $result->setStatus('validation_failed');
                    return $result;
                }
            }

            // Step 3: Create backup if needed
            if ($options->shouldAutoBackup() && $this->requiresBackup($report)) {
                $backup = $this->backupManager->createBackup(
                    $entityTypeCode,
                    \App\Eav\Schema\Backup\BackupType::FULL
                );
                $result->setBackupId($backup->getId());
                $result->addAppliedChange("Created backup #{$backup->getId()}");
            }

            // Step 4: Generate differences based on strategy
            $differences = $this->filterDifferencesByStrategy($report->getDifferences(), $options);

            if (empty($differences)) {
                $result->setStatus('no_applicable_changes');
                $result->addMetadata('message', 'No changes applicable for selected strategy');
                return $result;
            }

            // Step 5: Apply changes
            if (!$options->isDryRun()) {
                $this->applyChanges($differences, $result);
                
                // Step 6: Verify changes
                $this->verifyChanges($entityTypeCode, $result);
            } else {
                $result->setStatus('dry_run');
                $result->addMetadata('planned_changes', array_map(
                    fn($d) => $d->getDescription(),
                    $differences
                ));
            }

            $result->setSuccess(true);
            $result->setStatus($options->isDryRun() ? 'dry_run' : 'completed');
            
            $this->dispatchEvent('schema.sync.completed', ['result' => $result]);

        } catch (\Throwable $e) {
            $result->setSuccess(false);
            $result->setStatus('failed');
            $result->addError('Synchronization failed', $e);
            
            $this->dispatchEvent('schema.sync.failed', [
                'entity_type_code' => $entityTypeCode,
                'error' => $e->getMessage()
            ]);
        } finally {
            $result->setExecutionTime(microtime(true) - $startTime);
        }

        return $result;
    }

    /**
     * Synchronize all entity types
     */
    public function syncAll(SyncOptions $options): array
    {
        $results = [];
        $entityTypes = $this->registry->getAllEntityTypes();

        foreach ($entityTypes as $entityTypeCode => $config) {
            $results[$entityTypeCode] = $this->sync($entityTypeCode, $options);
        }

        return $results;
    }

    /**
     * Validate changes before applying
     */
    private function validateChanges(
        \App\Eav\Schema\SchemaAnalysisReport $report,
        SyncOptions $options,
        SyncResult $result
    ): void {
        // Check for destructive operations
        $destructive = array_filter(
            $report->getDifferences(),
            fn($d) => $d->isDestructive()
        );

        if (!empty($destructive) && !$options->isForce()) {
            $result->addError(
                'Destructive operations detected. Use --force to proceed or review changes carefully.'
            );
        }

        // Check risk level
        if ($report->getRiskLevel() === 'dangerous' && !$options->isForce()) {
            $result->addError(
                'Risk level is DANGEROUS. Use --force to proceed or review changes carefully.'
            );
        }

        // In additive mode, don't allow drops
        if ($options->isAdditive()) {
            $drops = array_filter(
                $report->getDifferences(),
                fn($d) => $d->getAction() === SchemaDifference::ACTION_DROP
            );

            if (!empty($drops)) {
                $result->addError(
                    'Additive strategy does not allow DROP operations. Use full strategy instead.'
                );
            }
        }
    }

    /**
     * Check if backup is required
     */
    private function requiresBackup(\App\Eav\Schema\SchemaAnalysisReport $report): bool
    {
        // Backup if there are destructive changes or medium+ risk
        return $report->getRiskScore() >= 40 || 
               !empty(array_filter($report->getDifferences(), fn($d) => $d->isDestructive()));
    }

    /**
     * Filter differences by strategy
     */
    private function filterDifferencesByStrategy(array $differences, SyncOptions $options): array
    {
        if ($options->isAdditive()) {
            // Only allow ADD operations
            return array_filter(
                $differences,
                fn($d) => $d->getAction() === SchemaDifference::ACTION_ADD
            );
        }

        if ($options->isFull()) {
            // Allow all operations
            return $differences;
        }

        return $differences;
    }

    /**
     * Apply schema changes
     */
    private function applyChanges(array $differences, SyncResult $result): void
    {
        $this->db->beginTransaction();

        try {
            foreach ($differences as $difference) {
                $this->applyDifference($difference, $result);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Apply single difference
     */
    private function applyDifference(SchemaDifference $difference, SyncResult $result): void
    {
        $metadata = $difference->getMetadata();

        switch ($difference->getType()) {
            case SchemaDifference::TYPE_MISSING_TABLE:
                $this->createTable($difference, $result);
                break;

            case SchemaDifference::TYPE_MISSING_COLUMN:
                $this->addColumn($difference, $result);
                break;

            case SchemaDifference::TYPE_MISSING_INDEX:
                $this->addIndex($difference, $result);
                break;

            case SchemaDifference::TYPE_TYPE_MISMATCH:
                $this->modifyColumn($difference, $result);
                break;

            case SchemaDifference::TYPE_ORPHANED_TABLE:
            case SchemaDifference::TYPE_ORPHANED_COLUMN:
                // Only drop if full strategy
                $this->dropStructure($difference, $result);
                break;

            default:
                $result->addError("Unknown difference type: {$difference->getType()}");
        }
    }

    /**
     * Create table
     */
    private function createTable(SchemaDifference $difference, SyncResult $result): void
    {
        $metadata = $difference->getMetadata();
        $tableName = $metadata['table_name'];
        $tableType = $metadata['table_type'] ?? 'unknown';

        if ($tableType === 'value') {
            $backendType = $metadata['backend_type'];
            $sql = $this->generateValueTableSQL($tableName, $backendType);
        } else {
            $sql = $this->generateEntityTableSQL($tableName);
        }

        $this->db->exec($sql);
        $result->addAppliedChange("Created table: $tableName", $metadata);
    }

    /**
     * Add column
     */
    private function addColumn(SchemaDifference $difference, SyncResult $result): void
    {
        $metadata = $difference->getMetadata();
        $tableName = $metadata['table_name'];
        $columnName = $metadata['column_name'];
        $definition = $metadata['expected_definition'] ?? [];

        $columnDef = $this->buildColumnDefinition($columnName, $definition);
        $sql = "ALTER TABLE `$tableName` ADD COLUMN $columnDef";

        $this->db->exec($sql);
        $result->addAppliedChange("Added column: $tableName.$columnName", $metadata);
    }

    /**
     * Add index
     */
    private function addIndex(SchemaDifference $difference, SyncResult $result): void
    {
        $metadata = $difference->getMetadata();
        $tableName = $metadata['table_name'];
        $columns = $metadata['index_columns'];
        $indexName = 'idx_' . implode('_', $columns);

        $columnList = implode(', ', array_map(fn($c) => "`$c`", $columns));
        $sql = "CREATE INDEX `$indexName` ON `$tableName` ($columnList)";

        $this->db->exec($sql);
        $result->addAppliedChange("Created index: $tableName.$indexName", $metadata);
    }

    /**
     * Modify column
     */
    private function modifyColumn(SchemaDifference $difference, SyncResult $result): void
    {
        $metadata = $difference->getMetadata();
        $tableName = $metadata['table_name'];
        $columnName = $metadata['column_name'];

        // This is complex - would need proper migration
        $result->addError("Column modification requires manual migration: $tableName.$columnName");
    }

    /**
     * Drop structure
     */
    private function dropStructure(SchemaDifference $difference, SyncResult $result): void
    {
        // Implement drop logic - very careful!
        $result->addError("Drop operations not yet implemented for safety");
    }

    /**
     * Generate value table SQL
     */
    private function generateValueTableSQL(string $tableName, string $backendType): string
    {
        $valueType = match($backendType) {
            'varchar' => 'VARCHAR(255)',
            'int' => 'INT',
            'decimal' => 'DECIMAL(12,4)',
            'datetime' => 'DATETIME',
            'text' => 'TEXT',
            default => 'VARCHAR(255)',
        };

        return "CREATE TABLE `$tableName` (
            `value_id` INT AUTO_INCREMENT PRIMARY KEY,
            `entity_id` INT NOT NULL,
            `attribute_id` INT NOT NULL,
            `value` $valueType,
            KEY `idx_entity_attribute` (`entity_id`, `attribute_id`),
            KEY `idx_attribute_value` (`attribute_id`, `value`(100))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    /**
     * Generate entity table SQL
     */
    private function generateEntityTableSQL(string $tableName): string
    {
        return "CREATE TABLE `$tableName` (
            `entity_id` INT AUTO_INCREMENT PRIMARY KEY,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    /**
     * Build column definition
     */
    private function buildColumnDefinition(string $columnName, array $definition): string
    {
        $type = $definition['type'] ?? 'VARCHAR(255)';
        $null = isset($definition['null']) && $definition['null'] ? 'NULL' : 'NOT NULL';
        $default = isset($definition['default']) ? "DEFAULT '{$definition['default']}'" : '';

        return "`$columnName` $type $null $default";
    }

    /**
     * Verify changes were applied correctly
     */
    private function verifyChanges(string $entityTypeCode, SyncResult $result): void
    {
        // Re-analyze to confirm sync
        $report = $this->analyzer->analyze($entityTypeCode);

        if ($report->hasDifferences()) {
            $result->addError('Verification failed: differences still exist after sync');
            $result->addMetadata('remaining_differences', count($report->getDifferences()));
        } else {
            $result->addMetadata('verification', 'passed');
        }
    }

    /**
     * Dispatch event
     */
    private function dispatchEvent(string $eventName, array $data = []): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch($eventName, $data);
        }
    }
}
