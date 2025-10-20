<?php

namespace App\Eav\Schema\Backup;

use App\Eav\Config\EntityTypeRegistry;
use Core\Database\Connection;

/**
 * Backup Manager
 * 
 * Manages schema and data backups for safe rollback capabilities.
 */
class BackupManager
{
    private Connection $db;
    private EntityTypeRegistry $registry;
    private string $storagePath;
    private int $nextBackupId = 1;

    public function __construct(
        Connection $db,
        EntityTypeRegistry $registry,
        string $storagePath = null
    ) {
        $this->db = $db;
        $this->registry = $registry;
        $this->storagePath = $storagePath ?? __DIR__ . '/../../../../storage/eav/backups';
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Create backup
     */
    public function createBackup(string $entityTypeCode, string $type): Backup
    {
        $backupId = $this->generateBackupId();
        $timestamp = date('Y-m-d_His');
        $fileName = "{$entityTypeCode}_{$type}_{$timestamp}.sql";
        $filePath = $this->storagePath . '/' . $fileName;

        $backup = new Backup($backupId, $entityTypeCode, $type, $filePath);

        try {
            switch ($type) {
                case BackupType::SCHEMA_ONLY:
                    $this->backupSchema($entityTypeCode, $filePath);
                    break;

                case BackupType::DATA_ONLY:
                    $this->backupData($entityTypeCode, $filePath);
                    break;

                case BackupType::FULL:
                    $this->backupFull($entityTypeCode, $filePath);
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid backup type: $type");
            }

            // Get file size
            if (file_exists($filePath)) {
                $backup->setFileSize(filesize($filePath));
            }

            // Record backup metadata
            $this->recordBackup($backup);

        } catch (\Throwable $e) {
            // Clean up failed backup
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            throw $e;
        }

        return $backup;
    }

    /**
     * Restore from backup
     */
    public function restore(int $backupId, RestoreOptions $options): RestoreResult
    {
        $startTime = microtime(true);
        $result = new RestoreResult($backupId);

        try {
            // Find backup
            $backup = $this->findBackup($backupId);
            if (!$backup) {
                $result->setSuccess(false);
                $result->setStatus('not_found');
                $result->addError("Backup #$backupId not found");
                return $result;
            }

            // Verify backup file exists
            if (!file_exists($backup->getStoragePath())) {
                $result->setSuccess(false);
                $result->setStatus('file_missing');
                $result->addError("Backup file not found: {$backup->getStoragePath()}");
                return $result;
            }

            if ($options->isVerifyOnly()) {
                // Just verify backup integrity
                $this->verifyBackup($backup, $result);
                $result->setStatus('verified');
            } else {
                // Perform actual restore
                $this->restoreFromFile($backup, $options, $result);
                $result->setStatus('restored');
            }

        } catch (\Throwable $e) {
            $result->setSuccess(false);
            $result->setStatus('failed');
            $result->addError("Restore failed: " . $e->getMessage());
        } finally {
            $result->setExecutionTime(microtime(true) - $startTime);
        }

        return $result;
    }

    /**
     * List backups
     */
    public function listBackups(string $entityTypeCode = null): array
    {
        // In a real implementation, this would query a backups registry table
        // For now, scan the backup directory
        $backups = [];
        
        $files = glob($this->storagePath . '/*.sql');
        foreach ($files as $file) {
            $fileName = basename($file);
            
            // Parse filename: {entity_type}_{type}_{timestamp}.sql
            if (preg_match('/^(.+)_(schema|data|full)_(\d{4}-\d{2}-\d{2}_\d{6})\.sql$/', $fileName, $matches)) {
                $fileEntityType = $matches[1];
                
                if ($entityTypeCode === null || $fileEntityType === $entityTypeCode) {
                    $backups[] = [
                        'entity_type_code' => $fileEntityType,
                        'type' => $matches[2],
                        'timestamp' => $matches[3],
                        'file_path' => $file,
                        'file_size' => filesize($file),
                    ];
                }
            }
        }

        return $backups;
    }

    /**
     * Backup schema only
     */
    private function backupSchema(string $entityTypeCode, string $filePath): void
    {
        $config = $this->registry->getEntityType($entityTypeCode);
        if (!$config) {
            throw new \RuntimeException("Entity type '$entityTypeCode' not found");
        }

        $entityTable = $config['entity_table'];
        $tables = [$entityTable];

        // Add value tables
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
        foreach ($backendTypes as $type) {
            $tables[] = "{$entityTable}_{$type}";
        }

        $sql = "-- Schema Backup: $entityTypeCode\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $sql .= $this->getCreateTableStatement($table);
                $sql .= "\n\n";
            }
        }

        file_put_contents($filePath, $sql);
    }

    /**
     * Backup data only
     */
    private function backupData(string $entityTypeCode, string $filePath): void
    {
        $config = $this->registry->getEntityType($entityTypeCode);
        if (!$config) {
            throw new \RuntimeException("Entity type '$entityTypeCode' not found");
        }

        $entityTable = $config['entity_table'];
        $tables = [$entityTable];

        // Add value tables
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
        foreach ($backendTypes as $type) {
            $tables[] = "{$entityTable}_{$type}";
        }

        $sql = "-- Data Backup: $entityTypeCode\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $sql .= $this->getTableData($table);
                $sql .= "\n\n";
            }
        }

        file_put_contents($filePath, $sql);
    }

    /**
     * Backup full (schema + data)
     */
    private function backupFull(string $entityTypeCode, string $filePath): void
    {
        $config = $this->registry->getEntityType($entityTypeCode);
        if (!$config) {
            throw new \RuntimeException("Entity type '$entityTypeCode' not found");
        }

        $entityTable = $config['entity_table'];
        $tables = [$entityTable];

        // Add value tables
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
        foreach ($backendTypes as $type) {
            $tables[] = "{$entityTable}_{$type}";
        }

        $sql = "-- Full Backup: $entityTypeCode\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $sql .= "-- Table: $table\n";
                $sql .= $this->getCreateTableStatement($table);
                $sql .= "\n";
                $sql .= $this->getTableData($table);
                $sql .= "\n\n";
            }
        }

        file_put_contents($filePath, $sql);
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        $result = $this->db->query("SHOW TABLES LIKE ?", [$tableName]);
        return !empty($result);
    }

    /**
     * Get CREATE TABLE statement
     */
    private function getCreateTableStatement(string $tableName): string
    {
        $result = $this->db->query("SHOW CREATE TABLE `$tableName`");
        if (empty($result)) {
            return "";
        }

        return "DROP TABLE IF EXISTS `$tableName`;\n" . $result[0]['Create Table'] . ";";
    }

    /**
     * Get table data as INSERT statements
     */
    private function getTableData(string $tableName): string
    {
        $rows = $this->db->query("SELECT * FROM `$tableName`");
        
        if (empty($rows)) {
            return "-- No data in table $tableName\n";
        }

        $sql = "-- Data for table $tableName\n";
        
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(function($val) {
                return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
            }, array_values($row));

            $sql .= "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES (";
            $sql .= implode(', ', $values) . ");\n";
        }

        return $sql;
    }

    /**
     * Restore from backup file
     */
    private function restoreFromFile(Backup $backup, RestoreOptions $options, RestoreResult $result): void
    {
        $sql = file_get_contents($backup->getStoragePath());
        
        $this->db->beginTransaction();

        try {
            // Execute SQL statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($stmt) => !empty($stmt) && strpos($stmt, '--') !== 0
            );

            foreach ($statements as $statement) {
                $this->db->exec($statement);
                
                // Track restored tables
                if (preg_match('/(?:CREATE|INSERT INTO)\s+`?(\w+)`?/i', $statement, $matches)) {
                    $result->addRestoredTable($matches[1]);
                }
            }

            $this->db->commit();
            $result->setSuccess(true);

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Verify backup integrity
     */
    private function verifyBackup(Backup $backup, RestoreResult $result): void
    {
        $content = file_get_contents($backup->getStoragePath());
        
        // Basic verification
        if (empty($content)) {
            $result->addError('Backup file is empty');
            $result->setSuccess(false);
            return;
        }

        if (strpos($content, 'CREATE TABLE') === false && strpos($content, 'INSERT INTO') === false) {
            $result->addError('Backup file does not contain valid SQL statements');
            $result->setSuccess(false);
            return;
        }

        $result->setSuccess(true);
    }

    /**
     * Find backup by ID
     */
    private function findBackup(int $backupId): ?Backup
    {
        // In a real implementation, query from backups registry table
        // For now, this is a placeholder
        return null;
    }

    /**
     * Record backup metadata
     */
    private function recordBackup(Backup $backup): void
    {
        // In a real implementation, insert into backups registry table
        // For now, this is a placeholder
    }

    /**
     * Generate unique backup ID
     */
    private function generateBackupId(): int
    {
        return $this->nextBackupId++;
    }
}
