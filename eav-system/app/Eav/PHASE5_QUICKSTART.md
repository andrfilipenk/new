# EAV Phase 5: Quick Start Guide

## Getting Started with Schema Management

This guide will help you quickly get started with the EAV Schema Management system.

---

## 🚀 Quick Setup

### 1. Run Database Migration

First, create the schema management metadata tables:

```bash
cd public
php migrate.php
```

This will create the following tables:
- `eav_schema_versions`
- `eav_schema_migrations`
- `eav_schema_backups`
- `eav_schema_conflicts`
- `eav_schema_analysis_log`

### 2. Verify Installation

```php
<?php
require_once 'bootstrap.php';

use App\Eav\Schema\Analysis\SchemaAnalyzer;
use App\Eav\Config\EntityTypeRegistry;
use Core\Database\Connection;

$db = new Connection(/* your config */);
$registry = new EntityTypeRegistry();
$analyzer = new SchemaAnalyzer($db, $registry);

// Check if system is working
$report = $analyzer->analyze('customer');
echo "Schema Analyzer is working!\n";
```

---

## 📋 Common Tasks

### Task 1: Check Schema Status

```php
use App\Eav\Schema\Analysis\SchemaAnalyzer;

$analyzer = new SchemaAnalyzer($db, $registry);
$report = $analyzer->analyze('customer');

echo "Status: {$report->getStatus()}\n";
echo "Differences: " . count($report->getDifferences()) . "\n";
echo "Risk Level: {$report->getRiskLevel()}\n";
```

### Task 2: Create a Backup

```php
use App\Eav\Schema\Backup\BackupManager;
use App\Eav\Schema\Backup\BackupType;

$backupManager = new BackupManager($db, $registry);
$backup = $backupManager->createBackup('customer', BackupType::FULL);

echo "Backup created: {$backup->getStoragePath()}\n";
```

### Task 3: Synchronize Schema (Safe Mode)

```php
use App\Eav\Schema\Sync\SynchronizationEngine;
use App\Eav\Schema\Sync\SyncOptions;

$syncEngine = new SynchronizationEngine(
    $analyzer, $comparator, $generator, 
    $executor, $backupManager, $registry, $db
);

// Step 1: Dry run to preview changes
$options = new SyncOptions(
    strategy: SyncOptions::STRATEGY_ADDITIVE,
    dryRun: true
);

$result = $syncEngine->sync('customer', $options);
print_r($result->getMetadata()['planned_changes']);

// Step 2: Apply changes if satisfied
$options = new SyncOptions(
    strategy: SyncOptions::STRATEGY_ADDITIVE,
    autoBackup: true
);

$result = $syncEngine->sync('customer', $options);
echo "Sync completed: {$result->getStatus()}\n";
```

### Task 4: Generate Migration File

```php
use App\Eav\Schema\Migration\MigrationGenerator;
use App\Eav\Schema\Migration\GeneratorOptions;

$generator = new MigrationGenerator();
$differences = $comparator->compare($config, $schema);

$options = new GeneratorOptions(name: "update_customer_schema");
$migration = $generator->generate($differences, $options);

echo "Migration file: {$migration->getFilePath()}\n";
```

---

## 🎯 Typical Workflow

### Daily Development Workflow

```php
// 1. Check for schema drift
$report = $analyzer->analyze('customer');

if ($report->hasDifferences()) {
    // 2. Review differences
    foreach ($report->getDifferences() as $diff) {
        echo "{$diff->getDescription()}\n";
    }
    
    // 3. Sync with additive strategy
    $options = new SyncOptions(strategy: SyncOptions::STRATEGY_ADDITIVE);
    $result = $syncEngine->sync('customer', $options);
}
```

### Pre-Production Deployment

```php
// 1. Create comprehensive backup
$backup = $backupManager->createBackup('customer', BackupType::FULL);

// 2. Analyze schema
$report = $analyzer->analyze('customer');

if ($report->getRiskLevel() !== 'safe') {
    echo "WARNING: High risk detected!\n";
    // Manual review required
}

// 3. Generate migration for version control
$differences = $comparator->compare($config, $schema);
$migration = $generator->generate($differences, new GeneratorOptions());

// 4. Validate migration
$validator = new MigrationValidator();
$validation = $validator->validate($migration, $differences);

if ($validation->isValid()) {
    // Commit migration to repository
    echo "Migration ready for deployment\n";
}
```

---

## ⚠️ Important Notes

### Safety Guidelines

1. **Always use dry run first** in production:
   ```php
   $options = new SyncOptions(dryRun: true);
   ```

2. **Create backups before risky operations**:
   ```php
   $backup = $backupManager->createBackup($entityType, BackupType::FULL);
   ```

3. **Use additive strategy in production**:
   ```php
   $options = new SyncOptions(strategy: SyncOptions::STRATEGY_ADDITIVE);
   ```

4. **Validate all generated migrations**:
   ```php
   $validation = $validator->validate($migration, $differences);
   ```

---

## 🔧 Configuration

### Basic Configuration

Create or update `config.php`:

```php
return [
    'eav' => [
        'schema' => [
            'backup_before_sync' => true,
            'backup_storage_path' => __DIR__ . '/storage/eav/backups',
            'migration_path' => __DIR__ . '/migrations',
            'default_strategy' => 'additive',
        ],
    ],
];
```

---

## 📊 Monitoring

### Check All Entity Types

```php
$reports = $analyzer->analyzeAll();

foreach ($reports as $entityType => $report) {
    echo "$entityType: {$report->getStatus()} ";
    echo "({$report->getRiskLevel()})\n";
}
```

### List Recent Backups

```php
$backups = $backupManager->listBackups();

foreach ($backups as $backup) {
    echo "{$backup['entity_type_code']}: ";
    echo "{$backup['timestamp']} - {$backup['type']}\n";
}
```

---

## 🐛 Troubleshooting

### Issue: Schema cache out of date

```php
$analyzer->clearCache();
$report = $analyzer->analyze('customer');
```

### Issue: Sync validation fails

```php
// Force sync (use with caution)
$options = new SyncOptions(
    force: true,
    skipValidation: true
);

$result = $syncEngine->sync('customer', $options);
```

### Issue: Restore backup

```php
$backupId = 5; // Your backup ID
$options = new RestoreOptions();
$result = $backupManager->restore($backupId, $options);

if ($result->isSuccess()) {
    echo "Restored successfully\n";
}
```

---

## 📚 Next Steps

1. Review the [Full Documentation](./PHASE5_IMPLEMENTATION.md)
2. Explore the [Examples File](../../examples/eav_schema_management_example.php)
3. Run the examples: `php examples/eav_schema_management_example.php`
4. Set up regular schema analysis in your deployment pipeline

---

## 🆘 Need Help?

- Check the comprehensive documentation: `app/Eav/PHASE5_IMPLEMENTATION.md`
- Review working examples: `examples/eav_schema_management_example.php`
- Examine the test files for usage patterns

---

**Quick Start Version**: 1.0  
**Last Updated**: October 19, 2025
