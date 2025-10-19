<?php

namespace App\Eav\Schema\Comparison;

use App\Eav\Schema\EntityTypeConfig;
use App\Eav\Schema\DatabaseSchema;
use App\Eav\Schema\DifferenceSet;
use App\Eav\Schema\SchemaDifference;

/**
 * Schema Comparator
 * 
 * Performs deep comparison between expected and actual schema states.
 */
class SchemaComparator
{
    private array $backendTypeMap = [
        'varchar' => ['VARCHAR', 'CHAR', 'TEXT'],
        'int' => ['INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'],
        'decimal' => ['DECIMAL', 'FLOAT', 'DOUBLE'],
        'datetime' => ['DATETIME', 'TIMESTAMP', 'DATE'],
        'text' => ['TEXT', 'MEDIUMTEXT', 'LONGTEXT'],
    ];

    /**
     * Compare expected configuration with actual database schema
     */
    public function compare(EntityTypeConfig $expected, DatabaseSchema $actual): DifferenceSet
    {
        $differences = new DifferenceSet($expected->getEntityTypeCode());

        // Phase 1: Structural Comparison
        $this->compareStructure($expected, $actual, $differences);

        // Phase 2: Constraint Comparison
        $this->compareConstraints($expected, $actual, $differences);

        // Phase 3: Optimization Comparison (Indexes)
        $this->compareIndexes($expected, $actual, $differences);

        return $differences;
    }

    /**
     * Calculate risk score for difference set
     */
    public function calculateRiskScore(DifferenceSet $differences): int
    {
        $baseScore = $differences->getTotalRiskScore();

        // Add environmental factors
        if ($this->isProduction()) {
            $baseScore += 10;
        }

        // Add data volume factor
        $dataVolume = $this->estimateDataVolume($differences->getEntityTypeCode());
        if ($dataVolume > 100000) {
            $baseScore += 20;
        } elseif ($dataVolume > 10000) {
            $baseScore += 10;
        }

        return min(100, $baseScore);
    }

    /**
     * Phase 1: Compare structural elements
     */
    private function compareStructure(
        EntityTypeConfig $expected,
        DatabaseSchema $actual,
        DifferenceSet $differences
    ): void {
        $entityTable = $expected->getEntityTable();
        $entityTypeCode = $expected->getEntityTypeCode();

        // Check entity table exists
        if (!$actual->hasTable($entityTable)) {
            $differences->addDifference(new SchemaDifference(
                $entityTypeCode,
                SchemaDifference::TYPE_MISSING_TABLE,
                SchemaDifference::SEVERITY_CRITICAL,
                SchemaDifference::ACTION_ADD,
                "Entity table '$entityTable' is missing",
                [
                    'table_name' => $entityTable,
                    'table_type' => 'entity',
                ],
                $entityTable
            ));
            return; // Can't proceed without entity table
        }

        // Check value tables
        $this->compareValueTables($expected, $actual, $differences);

        // Check columns in value tables
        $this->compareColumns($expected, $actual, $differences);
    }

    /**
     * Compare value tables
     */
    private function compareValueTables(
        EntityTypeConfig $expected,
        DatabaseSchema $actual,
        DifferenceSet $differences
    ): void {
        $entityTable = $expected->getEntityTable();
        $entityTypeCode = $expected->getEntityTypeCode();
        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];

        foreach ($backendTypes as $type) {
            $valueTable = "{$entityTable}_{$type}";

            if (!$actual->hasTable($valueTable)) {
                // Check if this backend type is actually used
                $usedAttributes = $expected->getAttributesByBackendType($type);
                
                if (!empty($usedAttributes)) {
                    $differences->addDifference(new SchemaDifference(
                        $entityTypeCode,
                        SchemaDifference::TYPE_MISSING_TABLE,
                        SchemaDifference::SEVERITY_CRITICAL,
                        SchemaDifference::ACTION_ADD,
                        "Value table '$valueTable' is missing (required for backend type '$type')",
                        [
                            'table_name' => $valueTable,
                            'backend_type' => $type,
                            'table_type' => 'value',
                            'affected_attributes' => array_keys($usedAttributes),
                        ],
                        $valueTable
                    ));
                }
            }
        }
    }

    /**
     * Compare columns in tables
     */
    private function compareColumns(
        EntityTypeConfig $expected,
        DatabaseSchema $actual,
        DifferenceSet $differences
    ): void {
        $entityTable = $expected->getEntityTable();
        $entityTypeCode = $expected->getEntityTypeCode();

        // Define expected columns for each value table
        $expectedColumns = [
            'value_id' => ['type' => 'INT', 'null' => false, 'key' => 'PRI'],
            'attribute_id' => ['type' => 'INT', 'null' => false],
            'entity_id' => ['type' => 'INT', 'null' => false],
            'value' => ['type' => null, 'null' => true], // Type varies by backend
        ];

        $backendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];

        foreach ($backendTypes as $backendType) {
            $valueTable = "{$entityTable}_{$backendType}";

            if (!$actual->hasTable($valueTable)) {
                continue; // Already reported as missing table
            }

            foreach ($expectedColumns as $columnName => $expectedDef) {
                if (!$actual->hasColumn($valueTable, $columnName)) {
                    $differences->addDifference(new SchemaDifference(
                        $entityTypeCode,
                        SchemaDifference::TYPE_MISSING_COLUMN,
                        SchemaDifference::SEVERITY_CRITICAL,
                        SchemaDifference::ACTION_ADD,
                        "Column '$columnName' is missing in table '$valueTable'",
                        [
                            'table_name' => $valueTable,
                            'column_name' => $columnName,
                            'expected_definition' => $expectedDef,
                        ],
                        $valueTable,
                        $columnName
                    ));
                } else {
                    // Check column definition
                    $actualColumn = $actual->getColumn($valueTable, $columnName);
                    $this->compareColumnDefinition(
                        $valueTable,
                        $columnName,
                        $expectedDef,
                        $actualColumn,
                        $backendType,
                        $differences,
                        $entityTypeCode
                    );
                }
            }
        }
    }

    /**
     * Compare column definition
     */
    private function compareColumnDefinition(
        string $tableName,
        string $columnName,
        array $expected,
        array $actual,
        string $backendType,
        DifferenceSet $differences,
        string $entityTypeCode
    ): void {
        // For 'value' column, check type compatibility with backend type
        if ($columnName === 'value' && $expected['type'] === null) {
            $actualType = strtoupper(preg_replace('/\(.*\)/', '', $actual['type']));
            $allowedTypes = $this->backendTypeMap[$backendType] ?? [];

            $isCompatible = false;
            foreach ($allowedTypes as $allowed) {
                if (strpos($actualType, $allowed) === 0) {
                    $isCompatible = true;
                    break;
                }
            }

            if (!$isCompatible) {
                $differences->addDifference(new SchemaDifference(
                    $entityTypeCode,
                    SchemaDifference::TYPE_TYPE_MISMATCH,
                    SchemaDifference::SEVERITY_HIGH,
                    SchemaDifference::ACTION_MODIFY,
                    "Column '$columnName' in '$tableName' has incompatible type: expected one of [" . 
                    implode(', ', $allowedTypes) . "], got '$actualType'",
                    [
                        'table_name' => $tableName,
                        'column_name' => $columnName,
                        'expected_types' => $allowedTypes,
                        'actual_type' => $actualType,
                        'backend_type' => $backendType,
                    ],
                    $tableName,
                    $columnName
                ));
            }
        }

        // Check nullability for non-value columns
        if ($columnName !== 'value' && isset($expected['null'])) {
            if ($expected['null'] !== $actual['null']) {
                $differences->addDifference(new SchemaDifference(
                    $entityTypeCode,
                    SchemaDifference::TYPE_CONSTRAINT_MISMATCH,
                    SchemaDifference::SEVERITY_MEDIUM,
                    SchemaDifference::ACTION_MODIFY,
                    "Column '$columnName' in '$tableName' has wrong nullability: expected " . 
                    ($expected['null'] ? 'NULL' : 'NOT NULL') . ", got " . 
                    ($actual['null'] ? 'NULL' : 'NOT NULL'),
                    [
                        'table_name' => $tableName,
                        'column_name' => $columnName,
                        'expected_null' => $expected['null'],
                        'actual_null' => $actual['null'],
                    ],
                    $tableName,
                    $columnName
                ));
            }
        }
    }

    /**
     * Phase 2: Compare constraints
     */
    private function compareConstraints(
        EntityTypeConfig $expected,
        DatabaseSchema $actual,
        DifferenceSet $differences
    ): void {
        // Check required attributes have proper constraints
        $entityTable = $expected->getEntityTable();
        $entityTypeCode = $expected->getEntityTypeCode();

        foreach ($expected->getRequiredAttributes() as $attrCode => $attrConfig) {
            $backendType = $attrConfig['backend_type'] ?? 'varchar';
            $valueTable = "{$entityTable}_{$backendType}";

            if (!$actual->hasTable($valueTable)) {
                continue;
            }

            // Required attributes should ideally have NOT NULL on value column
            // However, in EAV this is typically enforced at application level
            // So we'll just document it as info
            $column = $actual->getColumn($valueTable, 'value');
            if ($column && $column['null']) {
                $differences->addDifference(new SchemaDifference(
                    $entityTypeCode,
                    SchemaDifference::TYPE_CONSTRAINT_MISMATCH,
                    SchemaDifference::SEVERITY_INFO,
                    SchemaDifference::ACTION_MODIFY,
                    "Required attribute '$attrCode' allows NULL values in '$valueTable' (enforced at application level)",
                    [
                        'table_name' => $valueTable,
                        'attribute_code' => $attrCode,
                        'is_required' => true,
                        'allows_null' => true,
                    ],
                    $valueTable,
                    'value'
                ));
            }
        }
    }

    /**
     * Phase 3: Compare indexes
     */
    private function compareIndexes(
        EntityTypeConfig $expected,
        DatabaseSchema $actual,
        DifferenceSet $differences
    ): void {
        $entityTable = $expected->getEntityTable();
        $entityTypeCode = $expected->getEntityTypeCode();

        // Check indexes for searchable attributes
        foreach ($expected->getSearchableAttributes() as $attrCode => $attrConfig) {
            $backendType = $attrConfig['backend_type'] ?? 'varchar';
            $valueTable = "{$entityTable}_{$backendType}";

            if (!$actual->hasTable($valueTable)) {
                continue;
            }

            // Check for attribute_id + value index
            if (!$this->hasIndex($actual, $valueTable, ['attribute_id', 'value'])) {
                $differences->addDifference(new SchemaDifference(
                    $entityTypeCode,
                    SchemaDifference::TYPE_MISSING_INDEX,
                    SchemaDifference::SEVERITY_MEDIUM,
                    SchemaDifference::ACTION_ADD,
                    "Missing search index for attribute '$attrCode' in '$valueTable'",
                    [
                        'table_name' => $valueTable,
                        'attribute_code' => $attrCode,
                        'index_type' => 'search',
                        'index_columns' => ['attribute_id', 'value'],
                    ],
                    $valueTable
                ));
            }
        }

        // Check indexes for filterable attributes
        foreach ($expected->getFilterableAttributes() as $attrCode => $attrConfig) {
            $backendType = $attrConfig['backend_type'] ?? 'varchar';
            $valueTable = "{$entityTable}_{$backendType}";

            if (!$actual->hasTable($valueTable)) {
                continue;
            }

            // Check for entity_id + attribute_id index
            if (!$this->hasIndex($actual, $valueTable, ['entity_id', 'attribute_id'])) {
                $differences->addDifference(new SchemaDifference(
                    $entityTypeCode,
                    SchemaDifference::TYPE_MISSING_INDEX,
                    SchemaDifference::SEVERITY_LOW,
                    SchemaDifference::ACTION_ADD,
                    "Missing filter index for attribute '$attrCode' in '$valueTable'",
                    [
                        'table_name' => $valueTable,
                        'attribute_code' => $attrCode,
                        'index_type' => 'filter',
                        'index_columns' => ['entity_id', 'attribute_id'],
                    ],
                    $valueTable
                ));
            }
        }
    }

    /**
     * Check if index exists on columns
     */
    private function hasIndex(DatabaseSchema $schema, string $tableName, array $columns): bool
    {
        $indexes = $schema->getIndexes($tableName);

        foreach ($indexes as $index) {
            $indexColumns = $index['columns'] ?? [];
            
            // Check if all required columns are in the index (in order)
            $match = true;
            foreach ($columns as $i => $col) {
                if (!isset($indexColumns[$i]) || $indexColumns[$i] !== $col) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if running in production environment
     */
    private function isProduction(): bool
    {
        // This should be determined from environment config
        return ($_ENV['APP_ENV'] ?? 'development') === 'production';
    }

    /**
     * Estimate data volume for entity type
     */
    private function estimateDataVolume(string $entityTypeCode): int
    {
        // This would query the entity table to count rows
        // For now, return 0 as placeholder
        return 0;
    }
}
