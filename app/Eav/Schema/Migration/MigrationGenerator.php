<?php

namespace App\Eav\Schema\Migration;

use App\Eav\Schema\DifferenceSet;
use App\Eav\Schema\SchemaDifference;

/**
 * Migration Generator
 * 
 * Automatically generates migration files from schema differences.
 */
class MigrationGenerator
{
    private string $migrationPath;
    private string $templatePath;

    public function __construct(string $migrationPath = null)
    {
        $this->migrationPath = $migrationPath ?? __DIR__ . '/../../../../migrations';
        $this->templatePath = __DIR__ . '/Templates';
    }

    /**
     * Generate migration from differences
     */
    public function generate(DifferenceSet $differences, GeneratorOptions $options): Migration
    {
        $entityTypeCode = $differences->getEntityTypeCode();
        $timestamp = date('Y_m_d_His');
        $name = $options->getName() ?: "sync_eav_schema_{$entityTypeCode}";
        $fileName = "{$timestamp}_{$name}.php";
        $filePath = $this->migrationPath . '/' . $fileName;

        // Generate migration code
        $code = $this->generateMigrationCode($differences, $name, $timestamp);

        // Create migration object
        $migration = new Migration(
            $name,
            $filePath,
            $code,
            $differences->getEntityTypeCode()
        );

        // Write file if not preview mode
        if (!$options->isPreviewOnly()) {
            file_put_contents($filePath, $code);
            $migration->setCreated(true);
        }

        return $migration;
    }

    /**
     * Preview migration code without creating file
     */
    public function preview(DifferenceSet $differences): string
    {
        $name = "sync_eav_schema_{$differences->getEntityTypeCode()}";
        $timestamp = date('Y_m_d_His');
        return $this->generateMigrationCode($differences, $name, $timestamp);
    }

    /**
     * Generate migration code
     */
    private function generateMigrationCode(
        DifferenceSet $differences,
        string $name,
        string $timestamp
    ): string {
        $className = $this->generateClassName($name, $timestamp);
        $upCode = $this->generateUpMethod($differences);
        $downCode = $this->generateDownMethod($differences);

        return <<<PHP
<?php

use Core\Database\Connection;

/**
 * Migration: {$name}
 * Generated: {$timestamp}
 * Entity Type: {$differences->getEntityTypeCode()}
 */
class {$className}
{
    private Connection \$db;

    public function __construct(Connection \$db)
    {
        \$this->db = \$db;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
{$upCode}
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
{$downCode}
    }
}

PHP;
    }

    /**
     * Generate class name from migration name
     */
    private function generateClassName(string $name, string $timestamp): string
    {
        $parts = explode('_', $name);
        $className = implode('', array_map('ucfirst', $parts));
        return $className . '_' . $timestamp;
    }

    /**
     * Generate up() method code
     */
    private function generateUpMethod(DifferenceSet $differences): string
    {
        $code = [];

        foreach ($differences->getDifferences() as $difference) {
            $snippet = $this->generateUpSnippet($difference);
            if ($snippet) {
                $code[] = $snippet;
            }
        }

        if (empty($code)) {
            return "        // No changes to apply\n";
        }

        return implode("\n\n", $code);
    }

    /**
     * Generate down() method code
     */
    private function generateDownMethod(DifferenceSet $differences): string
    {
        $code = [];

        // Reverse the order for down migration
        $reversedDifferences = array_reverse($differences->getDifferences());

        foreach ($reversedDifferences as $difference) {
            $snippet = $this->generateDownSnippet($difference);
            if ($snippet) {
                $code[] = $snippet;
            }
        }

        if (empty($code)) {
            return "        // No changes to reverse\n";
        }

        return implode("\n\n", $code);
    }

    /**
     * Generate up code snippet for difference
     */
    private function generateUpSnippet(SchemaDifference $difference): ?string
    {
        $metadata = $difference->getMetadata();

        switch ($difference->getType()) {
            case SchemaDifference::TYPE_MISSING_TABLE:
                return $this->generateCreateTableSnippet($metadata);

            case SchemaDifference::TYPE_MISSING_COLUMN:
                return $this->generateAddColumnSnippet($metadata);

            case SchemaDifference::TYPE_MISSING_INDEX:
                return $this->generateAddIndexSnippet($metadata);

            case SchemaDifference::TYPE_TYPE_MISMATCH:
                return $this->generateModifyColumnSnippet($metadata);

            default:
                return "        // TODO: Handle {$difference->getType()}\n";
        }
    }

    /**
     * Generate down code snippet for difference
     */
    private function generateDownSnippet(SchemaDifference $difference): ?string
    {
        $metadata = $difference->getMetadata();

        switch ($difference->getType()) {
            case SchemaDifference::TYPE_MISSING_TABLE:
                $tableName = $metadata['table_name'];
                return <<<CODE
                // Drop table: {$tableName}
                \$this->db->exec("DROP TABLE IF EXISTS `{$tableName}`");
        CODE;

            case SchemaDifference::TYPE_MISSING_COLUMN:
                $tableName = $metadata['table_name'];
                $columnName = $metadata['column_name'];
                return <<<CODE
                // Remove column: {$tableName}.{$columnName}
                \$this->db->exec("ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`");
        CODE;

            case SchemaDifference::TYPE_MISSING_INDEX:
                $tableName = $metadata['table_name'];
                $columns = $metadata['index_columns'];
                $indexName = 'idx_' . implode('_', $columns);
                return <<<CODE
                // Drop index: {$indexName}
                \$this->db->exec("DROP INDEX `{$indexName}` ON `{$tableName}`");
        CODE;

            default:
                return "        // TODO: Reverse {$difference->getType()}\n";
        }
    }

    /**
     * Generate create table snippet
     */
    private function generateCreateTableSnippet(array $metadata): string
    {
        $tableName = $metadata['table_name'];
        $tableType = $metadata['table_type'] ?? 'unknown';

        if ($tableType === 'value') {
            $backendType = $metadata['backend_type'];
            $valueType = $this->getValueTypeForBackend($backendType);

            return <<<CODE
        // Create value table: {$tableName}
        \$sql = "CREATE TABLE `{$tableName}` (
            `value_id` INT AUTO_INCREMENT PRIMARY KEY,
            `entity_id` INT NOT NULL,
            `attribute_id` INT NOT NULL,
            `value` {$valueType},
            KEY `idx_entity_attribute` (`entity_id`, `attribute_id`),
            KEY `idx_attribute_value` (`attribute_id`, `value`(100))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        \$this->db->exec(\$sql);
CODE;
        } else {
            return <<<CODE
        // Create entity table: {$tableName}
        \$sql = "CREATE TABLE `{$tableName}` (
            `entity_id` INT AUTO_INCREMENT PRIMARY KEY,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        \$this->db->exec(\$sql);
CODE;
        }
    }

    /**
     * Generate add column snippet
     */
    private function generateAddColumnSnippet(array $metadata): string
    {
        $tableName = $metadata['table_name'];
        $columnName = $metadata['column_name'];
        $definition = $metadata['expected_definition'] ?? [];

        $type = $definition['type'] ?? 'VARCHAR(255)';
        $null = ($definition['null'] ?? true) ? 'NULL' : 'NOT NULL';

        return <<<CODE
        // Add column: {$tableName}.{$columnName}
        \$this->db->exec("ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$type} {$null}");
CODE;
    }

    /**
     * Generate add index snippet
     */
    private function generateAddIndexSnippet(array $metadata): string
    {
        $tableName = $metadata['table_name'];
        $columns = $metadata['index_columns'];
        $indexName = 'idx_' . implode('_', $columns);
        $columnList = implode('`, `', $columns);

        return <<<CODE
        // Create index: {$indexName}
        \$this->db->exec("CREATE INDEX `{$indexName}` ON `{$tableName}` (`{$columnList}`)");
CODE;
    }

    /**
     * Generate modify column snippet
     */
    private function generateModifyColumnSnippet(array $metadata): string
    {
        $tableName = $metadata['table_name'];
        $columnName = $metadata['column_name'];

        return <<<CODE
        // TODO: Modify column {$tableName}.{$columnName}
        // This requires careful data migration - implement manually
CODE;
    }

    /**
     * Get value type for backend type
     */
    private function getValueTypeForBackend(string $backendType): string
    {
        return match($backendType) {
            'varchar' => 'VARCHAR(255)',
            'int' => 'INT',
            'decimal' => 'DECIMAL(12,4)',
            'datetime' => 'DATETIME',
            'text' => 'TEXT',
            default => 'VARCHAR(255)',
        };
    }
}
