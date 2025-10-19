<?php

use Core\Database\Connection;

/**
 * Create EAV Schema Management Tables
 * 
 * Tables for tracking schema versions, migrations, backups, and conflicts.
 */
class CreateEavSchemaManagementTables_2025_10_19_120000
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        // Create schema versions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `eav_schema_versions` (
                `version_id` INT AUTO_INCREMENT PRIMARY KEY,
                `entity_type_code` VARCHAR(64) NOT NULL,
                `version` VARCHAR(32) NOT NULL,
                `configuration_hash` VARCHAR(64) NOT NULL,
                `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `applied_by` VARCHAR(100),
                `status` ENUM('pending', 'applied', 'failed', 'rolled_back') DEFAULT 'applied',
                KEY `idx_entity_type` (`entity_type_code`),
                KEY `idx_version` (`version`),
                KEY `idx_applied_at` (`applied_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create schema migrations table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `eav_schema_migrations` (
                `migration_id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration_name` VARCHAR(255) NOT NULL,
                `entity_type_code` VARCHAR(64) NOT NULL,
                `status` ENUM('pending', 'executing', 'completed', 'failed', 'rolled_back') DEFAULT 'pending',
                `executed_at` DATETIME,
                `execution_time` DECIMAL(10,3),
                `error_message` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_migration` (`migration_name`),
                KEY `idx_entity_type` (`entity_type_code`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create schema backups table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `eav_schema_backups` (
                `backup_id` INT AUTO_INCREMENT PRIMARY KEY,
                `entity_type_code` VARCHAR(64) NOT NULL,
                `backup_type` ENUM('schema', 'data', 'full') NOT NULL,
                `storage_path` VARCHAR(500) NOT NULL,
                `file_size_bytes` BIGINT DEFAULT 0,
                `configuration_snapshot` TEXT,
                `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `created_by` VARCHAR(100),
                `metadata` JSON,
                KEY `idx_entity_type` (`entity_type_code`),
                KEY `idx_backup_type` (`backup_type`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create schema conflicts table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `eav_schema_conflicts` (
                `conflict_id` INT AUTO_INCREMENT PRIMARY KEY,
                `entity_type_code` VARCHAR(64) NOT NULL,
                `conflict_type` VARCHAR(100) NOT NULL,
                `description` TEXT,
                `detected_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `resolved_at` DATETIME,
                `resolved_by` VARCHAR(100),
                `resolution` TEXT,
                `status` ENUM('detected', 'in_progress', 'resolved', 'ignored') DEFAULT 'detected',
                KEY `idx_entity_type` (`entity_type_code`),
                KEY `idx_status` (`status`),
                KEY `idx_detected_at` (`detected_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Create schema analysis log table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `eav_schema_analysis_log` (
                `log_id` INT AUTO_INCREMENT PRIMARY KEY,
                `entity_type_code` VARCHAR(64) NOT NULL,
                `analysis_type` VARCHAR(50) NOT NULL,
                `differences_count` INT DEFAULT 0,
                `risk_score` INT DEFAULT 0,
                `risk_level` VARCHAR(20),
                `report_data` JSON,
                `analyzed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `analyzed_by` VARCHAR(100),
                KEY `idx_entity_type` (`entity_type_code`),
                KEY `idx_analyzed_at` (`analyzed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS `eav_schema_analysis_log`");
        $this->db->exec("DROP TABLE IF EXISTS `eav_schema_conflicts`");
        $this->db->exec("DROP TABLE IF EXISTS `eav_schema_backups`");
        $this->db->exec("DROP TABLE IF EXISTS `eav_schema_migrations`");
        $this->db->exec("DROP TABLE IF EXISTS `eav_schema_versions`");
    }
}
