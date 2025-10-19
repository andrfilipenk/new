<?php

/**
 * EAV Phase 5: Schema Management Examples
 * 
 * This file demonstrates the usage of the EAV Schema Management system.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Eav\Schema\Analysis\SchemaAnalyzer;
use App\Eav\Schema\Comparison\SchemaComparator;
use App\Eav\Schema\Sync\SynchronizationEngine;
use App\Eav\Schema\Sync\SyncOptions;
use App\Eav\Schema\Migration\MigrationGenerator;
use App\Eav\Schema\Migration\GeneratorOptions;
use App\Eav\Schema\Migration\MigrationValidator;
use App\Eav\Schema\Backup\BackupManager;
use App\Eav\Schema\Backup\BackupType;
use App\Eav\Schema\Backup\RestoreOptions;
use App\Eav\Config\EntityTypeRegistry;
use Core\Database\Connection;

// Initialize dependencies
$db = new Connection(/* config */);
$registry = new EntityTypeRegistry();

echo "=== EAV Schema Management Examples ===\n\n";

// ============================================================================
// Example 1: Analyze Schema
// ============================================================================
echo "1. Analyzing Schema\n";
echo str_repeat('-', 50) . "\n";

$analyzer = new SchemaAnalyzer($db, $registry);

// Analyze specific entity type
$entityTypeCode = 'customer';
$report = $analyzer->analyze($entityTypeCode);

echo "Entity Type: {$report->getEntityTypeCode()}\n";
echo "Status: {$report->getStatus()}\n";
echo "Risk Score: {$report->getRiskScore()} ({$report->getRiskLevel()})\n";
echo "Differences Found: " . count($report->getDifferences()) . "\n\n";

if ($report->hasDifferences()) {
    echo "Differences:\n";
    foreach ($report->getDifferences() as $diff) {
        echo "  - [{$diff->getSeverity()}] {$diff->getDescription()}\n";
    }
    echo "\n";
}

echo "Recommendations:\n";
foreach ($report->getRecommendations() as $rec) {
    echo "  • $rec\n";
}
echo "\n\n";

// ============================================================================
// Example 2: Compare Schema
// ============================================================================
echo "2. Comparing Schema\n";
echo str_repeat('-', 50) . "\n";

$analyzer = new SchemaAnalyzer($db, $registry);

$comparator = new SchemaComparator();

// Get configuration and actual schema
$config = $analyzer->loadConfiguration($entityTypeCode);
$schema = $analyzer->getPhysicalSchema($entityTypeCode);

// Compare
$differences = $comparator->compare($config, $schema);

echo "Total Differences: {$differences->count()}\n";
echo "Risk Score: {$differences->getTotalRiskScore()}\n";
echo "Has Destructive Changes: " . ($differences->hasDestructiveDifferences() ? 'Yes' : 'No') . "\n";

if ($differences->hasDifferences()) {
    echo "\nDifferences by Action:\n";
    
    $addDiffs = $differences->getDifferencesByAction(\App\Eav\Schema\SchemaDifference::ACTION_ADD);
    echo "  ADD: " . count($addDiffs) . "\n";
    
    $modifyDiffs = $differences->getDifferencesByAction(\App\Eav\Schema\SchemaDifference::ACTION_MODIFY);
    echo "  MODIFY: " . count($modifyDiffs) . "\n";
    
    $dropDiffs = $differences->getDifferencesByAction(\App\Eav\Schema\SchemaDifference::ACTION_DROP);
    echo "  DROP: " . count($dropDiffs) . "\n";
}
echo "\n\n";

// ============================================================================
// Example 3: Create Backup
// ============================================================================
echo "3. Creating Backup\n";
echo str_repeat('-', 50) . "\n";

$backupManager = new BackupManager($db, $registry);

// Create full backup
$backup = $backupManager->createBackup($entityTypeCode, BackupType::FULL);

echo "Backup Created:\n";
echo "  ID: {$backup->getId()}\n";
echo "  Type: {$backup->getType()}\n";
echo "  Size: " . number_format($backup->getFileSize()) . " bytes\n";
echo "  Path: {$backup->getStoragePath()}\n";
echo "  Created: {$backup->getCreatedAt()->format('Y-m-d H:i:s')}\n";
echo "\n\n";

// ============================================================================
// Example 4: Generate Migration
// ============================================================================
echo "4. Generating Migration\n";
echo str_repeat('-', 50) . "\n";

$generator = new MigrationGenerator();
$validator = new MigrationValidator();

// Generate migration from differences
$generatorOptions = new GeneratorOptions(
    name: "sync_customer_schema",
    previewOnly: true // Preview only, don't create file
);

$migration = $generator->generate($differences, $generatorOptions);

echo "Migration Generated:\n";
echo "  Name: {$migration->getName()}\n";
echo "  File: {$migration->getFilePath()}\n";
echo "  Entity Type: {$migration->getEntityTypeCode()}\n\n";

// Validate migration
$validationResult = $validator->validate($migration, $differences);

echo "Validation Result:\n";
echo "  Valid: " . ($validationResult->isValid() ? 'Yes' : 'No') . "\n";
echo "  Risk Level: {$validationResult->getRiskLevel()}\n";
echo "  Auto-approve: " . ($validationResult->isAutoApprove() ? 'Yes' : 'No') . "\n";

if ($validationResult->hasErrors()) {
    echo "  Errors:\n";
    foreach ($validationResult->getErrors() as $error) {
        echo "    - $error\n";
    }
}

if ($validationResult->hasWarnings()) {
    echo "  Warnings:\n";
    foreach ($validationResult->getWarnings() as $warning) {
        echo "    - $warning\n";
    }
}

echo "\n\nMigration Code Preview:\n";
echo str_repeat('=', 80) . "\n";
echo $migration->getCode();
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// Example 5: Synchronize Schema (Dry Run)
// ============================================================================
echo "5. Synchronizing Schema (Dry Run)\n";
echo str_repeat('-', 50) . "\n";

$syncEngine = new SynchronizationEngine(
    $analyzer,
    $comparator,
    $generator,
    new \App\Eav\Schema\Migration\MigrationExecutor($db),
    $backupManager,
    $registry,
    $db
);

// Dry run sync
$syncOptions = new SyncOptions(
    strategy: SyncOptions::STRATEGY_ADDITIVE,
    dryRun: true
);

$syncResult = $syncEngine->sync($entityTypeCode, $syncOptions);

echo "Sync Result:\n";
echo "  Success: " . ($syncResult->isSuccess() ? 'Yes' : 'No') . "\n";
echo "  Status: {$syncResult->getStatus()}\n";
echo "  Execution Time: " . number_format($syncResult->getExecutionTime(), 3) . "s\n";

if ($syncResult->getBackupId()) {
    echo "  Backup ID: {$syncResult->getBackupId()}\n";
}

echo "\nPlanned Changes:\n";
$plannedChanges = $syncResult->getMetadata()['planned_changes'] ?? [];
foreach ($plannedChanges as $change) {
    echo "  • $change\n";
}

if ($syncResult->hasErrors()) {
    echo "\nErrors:\n";
    foreach ($syncResult->getErrors() as $error) {
        echo "  - {$error['message']}\n";
    }
}
echo "\n\n";

// ============================================================================
// Example 6: Restore from Backup
// ============================================================================
echo "6. Restoring from Backup (Verify Only)\n";
echo str_repeat('-', 50) . "\n";

$restoreOptions = new RestoreOptions(
    verifyOnly: true // Only verify, don't restore
);

$restoreResult = $backupManager->restore($backup->getId(), $restoreOptions);

echo "Restore Result:\n";
echo "  Success: " . ($restoreResult->isSuccess() ? 'Yes' : 'No') . "\n";
echo "  Status: {$restoreResult->getStatus()}\n";
echo "  Execution Time: " . number_format($restoreResult->getExecutionTime(), 3) . "s\n";

if ($restoreResult->hasErrors()) {
    echo "  Errors:\n";
    foreach ($restoreResult->getErrors() as $error) {
        echo "    - $error\n";
    }
}
echo "\n\n";

// ============================================================================
// Example 7: List Backups
// ============================================================================
echo "7. Listing Backups\n";
echo str_repeat('-', 50) . "\n";

$backups = $backupManager->listBackups($entityTypeCode);

echo "Available Backups for '$entityTypeCode': " . count($backups) . "\n\n";

foreach ($backups as $backup) {
    echo "  • {$backup['timestamp']} - {$backup['type']} - " . 
         number_format($backup['file_size']) . " bytes\n";
}
echo "\n\n";

// ============================================================================
// Example 8: Detect Orphaned Structures
// ============================================================================
echo "8. Detecting Orphaned Structures\n";
echo str_repeat('-', 50) . "\n";

$orphans = $analyzer->detectOrphans();

echo "Orphaned Structures Found: " . count($orphans) . "\n\n";

foreach ($orphans as $orphan) {
    echo "  • [{$orphan['type']}] {$orphan['name']}\n";
    echo "    {$orphan['description']}\n";
}

echo "\n\n=== Examples Complete ===\n";
