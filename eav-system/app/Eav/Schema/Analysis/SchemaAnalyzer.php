<?php

namespace App\Eav\Schema\Analysis;

use App\Eav\Schema\SchemaAnalysisReport;
use App\Eav\Schema\DatabaseSchema;
use App\Eav\Schema\EntityTypeConfig;
use App\Eav\Schema\DifferenceSet;
use App\Eav\Config\EntityTypeRegistry;
use Core\Database\Connection;

/**
 * Schema Analyzer
 * 
 * Analyzes schema differences between configuration and database.
 */
class SchemaAnalyzer
{
    private Connection $db;
    private EntityTypeRegistry $registry;
    private array $schemaCache = [];
    private int $cacheLifetime = 300; // 5 minutes

    public function __construct(Connection $db, EntityTypeRegistry $registry)
    {
        $this->db = $db;
        $this->registry = $registry;
    }

    /**
     * Analyze schema for specific entity type
     */
    public function analyze(string $entityTypeCode): SchemaAnalysisReport
    {
        // Load configuration
        $config = $this->loadConfiguration($entityTypeCode);
        
        // Load physical schema
        $schema = $this->getPhysicalSchema($entityTypeCode);
        
        // Create report
        $report = new SchemaAnalysisReport($entityTypeCode);
        
        // Check entity table
        $this->checkEntityTable($config, $schema, $report);
        
        // Check value tables
        $this->checkValueTables($config, $schema, $report);
        
        // Check attributes
        $this->checkAttributes($config, $schema, $report);
        
        // Check indexes
        $this->checkIndexes($config, $schema, $report);
        
        // Check for orphaned structures
        $this->checkOrphans($config, $schema, $report);
        
        // Calculate risk score
        $this->calculateRiskScore($report);
        
        // Generate recommendations
        $this->generateRecommendations($report);
        
        return $report;
    }

    /**
     * Analyze all registered entity types
     */
    public function analyzeAll(): array
    {
        $reports = [];
        $entityTypes = $this->registry->getAllEntityTypes();
        
        foreach ($entityTypes as $entityTypeCode => $config) {
            $reports[$entityTypeCode] = $this->analyze($entityTypeCode);
        }
        
        return $reports;
    }

    /**
     * Detect orphaned database structures
     */
    public function detectOrphans(): array
    {
        $orphans = [];
        $allTables = $this->getAllEavTables();
        $configuredTables = $this->getConfiguredTables();
        
        foreach ($allTables as $table) {
            if (!in_array($table, $configuredTables)) {
                $orphans[] = [
                    'type' => 'table',
                    'name' => $table,
                    'description' => "Table '$table' exists but is not configured",
                ];
            }
        }
        
        return $orphans;
    }

    /**
     * Load entity type configuration
     */
    private function loadConfiguration(string $entityTypeCode): EntityTypeConfig
    {
        $config = $this->registry->getEntityType($entityTypeCode);
        
        if (!$config) {
            throw new \RuntimeException("Entity type '$entityTypeCode' not found in registry");
        }
        
        $attributes = $this->registry->getAttributes($entityTypeCode);
        
        return new EntityTypeConfig(
            $entityTypeCode,
            $config['entity_table'],
            $attributes,
            $config['value_tables'] ?? []
        );
    }

    /**
     * Get physical database schema
     */
    private function getPhysicalSchema(string $entityTypeCode): DatabaseSchema
    {
        $cacheKey = "schema_{$entityTypeCode}";
        
        // Check cache
        if (isset($this->schemaCache[$cacheKey])) {
            $cached = $this->schemaCache[$cacheKey];
            if ($cached['expires_at'] > time()) {
                return $cached['schema'];
            }
        }
        
        $schema = new DatabaseSchema($entityTypeCode);
        
        // Load entity table schema
        $config = $this->registry->getEntityType($entityTypeCode);
        $entityTable = $config['entity_table'];
        
        if ($this->tableExists($entityTable)) {
            $schema->addTable($entityTable, $this->getTableStructure($entityTable));
        }
        
        // Load value tables schema
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
        foreach ($backendTypes as $type) {
            $valueTable = "{$entityTable}_{$type}";
            if ($this->tableExists($valueTable)) {
                $schema->addTable($valueTable, $this->getTableStructure($valueTable));
            }
        }
        
        // Cache the schema
        $this->schemaCache[$cacheKey] = [
            'schema' => $schema,
            'expires_at' => time() + $this->cacheLifetime,
        ];
        
        return $schema;
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->db->query($sql, [$tableName]);
        return !empty($result);
    }

    /**
     * Get table structure
     */
    private function getTableStructure(string $tableName): array
    {
        $structure = [
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
        ];
        
        // Get columns
        $columns = $this->db->query("SHOW COLUMNS FROM `$tableName`");
        foreach ($columns as $column) {
            $structure['columns'][$column['Field']] = [
                'type' => $column['Type'],
                'null' => $column['Null'] === 'YES',
                'key' => $column['Key'],
                'default' => $column['Default'],
                'extra' => $column['Extra'],
            ];
        }
        
        // Get indexes
        $indexes = $this->db->query("SHOW INDEXES FROM `$tableName`");
        foreach ($indexes as $index) {
            $indexName = $index['Key_name'];
            if (!isset($structure['indexes'][$indexName])) {
                $structure['indexes'][$indexName] = [
                    'name' => $indexName,
                    'unique' => $index['Non_unique'] == 0,
                    'columns' => [],
                    'type' => $index['Index_type'] ?? 'BTREE',
                ];
            }
            $structure['indexes'][$indexName]['columns'][] = $index['Column_name'];
        }
        
        return $structure;
    }

    /**
     * Check entity table existence
     */
    private function checkEntityTable(
        EntityTypeConfig $config,
        DatabaseSchema $schema,
        SchemaAnalysisReport $report
    ): void {
        $entityTable = $config->getEntityTable();
        
        if (!$schema->hasTable($entityTable)) {
            $report->addDifference(
                new \App\Eav\Schema\SchemaDifference(
                    $config->getEntityTypeCode(),
                    \App\Eav\Schema\SchemaDifference::TYPE_MISSING_TABLE,
                    \App\Eav\Schema\SchemaDifference::SEVERITY_CRITICAL,
                    \App\Eav\Schema\SchemaDifference::ACTION_ADD,
                    "Entity table '$entityTable' does not exist",
                    ['table_name' => $entityTable],
                    $entityTable
                )
            );
        }
    }

    /**
     * Check value tables existence
     */
    private function checkValueTables(
        EntityTypeConfig $config,
        DatabaseSchema $schema,
        SchemaAnalysisReport $report
    ): void {
        $entityTable = $config->getEntityTable();
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
        
        foreach ($backendTypes as $type) {
            $valueTable = "{$entityTable}_{$type}";
            
            if (!$schema->hasTable($valueTable)) {
                $report->addDifference(
                    new \App\Eav\Schema\SchemaDifference(
                        $config->getEntityTypeCode(),
                        \App\Eav\Schema\SchemaDifference::TYPE_MISSING_TABLE,
                        \App\Eav\Schema\SchemaDifference::SEVERITY_CRITICAL,
                        \App\Eav\Schema\SchemaDifference::ACTION_ADD,
                        "Value table '$valueTable' does not exist",
                        [
                            'table_name' => $valueTable,
                            'backend_type' => $type,
                        ],
                        $valueTable
                    )
                );
            }
        }
    }

    /**
     * Check attributes mapping to columns
     */
    private function checkAttributes(
        EntityTypeConfig $config,
        DatabaseSchema $schema,
        SchemaAnalysisReport $report
    ): void {
        $entityTable = $config->getEntityTable();
        $attributes = $config->getAttributes();
        
        foreach ($attributes as $attrCode => $attrConfig) {
            $backendType = $attrConfig['backend_type'] ?? 'varchar';
            $valueTable = "{$entityTable}_{$backendType}";
            
            // Skip if value table doesn't exist (already reported)
            if (!$schema->hasTable($valueTable)) {
                continue;
            }
            
            // Check if value column exists
            if (!$schema->hasColumn($valueTable, 'value')) {
                $report->addDifference(
                    new \App\Eav\Schema\SchemaDifference(
                        $config->getEntityTypeCode(),
                        \App\Eav\Schema\SchemaDifference::TYPE_MISSING_COLUMN,
                        \App\Eav\Schema\SchemaDifference::SEVERITY_CRITICAL,
                        \App\Eav\Schema\SchemaDifference::ACTION_ADD,
                        "Value column missing in table '$valueTable'",
                        [
                            'table_name' => $valueTable,
                            'column_name' => 'value',
                            'attribute_code' => $attrCode,
                        ],
                        $valueTable,
                        'value'
                    )
                );
            }
        }
    }

    /**
     * Check indexes for searchable/filterable attributes
     */
    private function checkIndexes(
        EntityTypeConfig $config,
        DatabaseSchema $schema,
        SchemaAnalysisReport $report
    ): void {
        $entityTable = $config->getEntityTable();
        
        // Check searchable attributes
        foreach ($config->getSearchableAttributes() as $attrCode => $attrConfig) {
            $backendType = $attrConfig['backend_type'] ?? 'varchar';
            $valueTable = "{$entityTable}_{$backendType}";
            
            if (!$schema->hasTable($valueTable)) {
                continue;
            }
            
            // Check for index on (attribute_id, value)
            $hasIndex = $this->hasAttributeValueIndex($schema, $valueTable);
            
            if (!$hasIndex) {
                $report->addDifference(
                    new \App\Eav\Schema\SchemaDifference(
                        $config->getEntityTypeCode(),
                        \App\Eav\Schema\SchemaDifference::TYPE_MISSING_INDEX,
                        \App\Eav\Schema\SchemaDifference::SEVERITY_MEDIUM,
                        \App\Eav\Schema\SchemaDifference::ACTION_ADD,
                        "Missing index for searchable attribute '$attrCode' in table '$valueTable'",
                        [
                            'table_name' => $valueTable,
                            'attribute_code' => $attrCode,
                            'index_columns' => ['attribute_id', 'value'],
                        ],
                        $valueTable
                    )
                );
            }
        }
    }

    /**
     * Check for attribute value index
     */
    private function hasAttributeValueIndex(DatabaseSchema $schema, string $tableName): bool
    {
        $indexes = $schema->getIndexes($tableName);
        
        foreach ($indexes as $index) {
            $columns = $index['columns'] ?? [];
            if (in_array('attribute_id', $columns) && in_array('value', $columns)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for orphaned database structures
     */
    private function checkOrphans(
        EntityTypeConfig $config,
        DatabaseSchema $schema,
        SchemaAnalysisReport $report
    ): void {
        // This is a basic check - can be enhanced
        // For now, we'll skip orphan detection in per-entity analysis
    }

    /**
     * Calculate risk score for report
     */
    private function calculateRiskScore(SchemaAnalysisReport $report): void
    {
        $totalRisk = 0;
        
        foreach ($report->getDifferences() as $difference) {
            $totalRisk += $difference->getRiskScore();
        }
        
        $report->setRiskScore(min(100, $totalRisk));
        
        // Update status based on differences
        if (!$report->hasDifferences()) {
            $report->setStatus('in_sync');
        } elseif ($report->getRiskLevel() === 'dangerous' || $report->getRiskLevel() === 'high') {
            $report->setStatus('critical');
        } elseif ($report->getRiskLevel() === 'medium') {
            $report->setStatus('needs_attention');
        } else {
            $report->setStatus('drift_detected');
        }
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(SchemaAnalysisReport $report): void
    {
        if (!$report->hasDifferences()) {
            $report->addRecommendation('Schema is in sync with configuration. No action needed.');
            return;
        }
        
        $hasCritical = !empty(array_filter(
            $report->getDifferences(),
            fn($d) => $d->getSeverity() === \App\Eav\Schema\SchemaDifference::SEVERITY_CRITICAL
        ));
        
        if ($hasCritical) {
            $report->addRecommendation('Critical differences detected. Immediate synchronization recommended.');
            $report->addRecommendation('Run: php cli.php eav:schema:sync ' . $report->getEntityTypeCode());
        }
        
        $hasDestructive = !empty(array_filter(
            $report->getDifferences(),
            fn($d) => $d->isDestructive()
        ));
        
        if ($hasDestructive) {
            $report->addRecommendation('Destructive operations detected. Create backup before synchronization.');
            $report->addRecommendation('Run: php cli.php eav:backup:create ' . $report->getEntityTypeCode());
        }
        
        if ($report->getRiskLevel() === 'safe' || $report->getRiskLevel() === 'low') {
            $report->addRecommendation('Safe to sync automatically. Use --dry-run first to preview changes.');
        } else {
            $report->addRecommendation('Review differences carefully before synchronization.');
            $report->addRecommendation('Use --dry-run option to preview changes without applying them.');
        }
    }

    /**
     * Get all EAV tables
     */
    private function getAllEavTables(): array
    {
        $sql = "SHOW TABLES LIKE '%_entity%'";
        $result = $this->db->query($sql);
        
        $tables = [];
        foreach ($result as $row) {
            $tables[] = array_values($row)[0];
        }
        
        return $tables;
    }

    /**
     * Get configured tables
     */
    private function getConfiguredTables(): array
    {
        $tables = [];
        $entityTypes = $this->registry->getAllEntityTypes();
        
        foreach ($entityTypes as $code => $config) {
            $entityTable = $config['entity_table'];
            $tables[] = $entityTable;
            
            // Add value tables
            $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
            foreach ($backendTypes as $type) {
                $tables[] = "{$entityTable}_{$type}";
            }
        }
        
        return $tables;
    }

    /**
     * Clear schema cache
     */
    public function clearCache(): void
    {
        $this->schemaCache = [];
    }
}
