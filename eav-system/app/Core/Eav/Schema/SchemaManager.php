<?php
// app/Core/Eav/Schema/SchemaManager.php
namespace Core\Eav\Schema;

use Core\Database\Database;
use Core\Eav\Model\EntityType;
use Core\Eav\Exception\SynchronizationException;
use Core\Di\Injectable;

/**
 * Orchestrates database schema creation and synchronization
 */
class SchemaManager
{
    use Injectable;

    private Database $db;
    private StructureBuilder $structureBuilder;
    private MigrationGenerator $migrationGenerator;
    private array $schemaState = [];

    public function __construct(
        Database $db,
        StructureBuilder $structureBuilder,
        MigrationGenerator $migrationGenerator
    ) {
        $this->db = $db;
        $this->structureBuilder = $structureBuilder;
        $this->migrationGenerator = $migrationGenerator;
    }

    /**
     * Initialize base EAV schema
     */
    public function initialize(): bool
    {
        try {
            // Check if already initialized
            if ($this->isInitialized()) {
                return true;
            }

            $this->db->beginTransaction();

            // Create entity type table
            $this->createTableFromBlueprint($this->structureBuilder->buildEntityTypeTable());

            // Create attribute metadata table
            $this->createTableFromBlueprint($this->structureBuilder->buildAttributeTable());

            // Create value tables for each backend type
            foreach ($this->structureBuilder->getBackendTypes() as $type) {
                $this->createTableFromBlueprint($this->structureBuilder->buildValueTable($type));
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new SynchronizationException(
                "Failed to initialize EAV schema: " . $e->getMessage(),
                "Schema initialization failed",
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Synchronize entity type schema with configuration
     */
    public function synchronize(EntityType $entityType): bool
    {
        try {
            $this->db->beginTransaction();

            // Ensure base schema exists
            if (!$this->isInitialized()) {
                $this->initialize();
            }

            // Check if entity type exists
            $existingType = $this->db->table('eav_entity_type')
                ->where('entity_code', $entityType->getCode())
                ->first();

            if (!$existingType) {
                // Create new entity type
                $entityTypeId = $this->createEntityType($entityType);
                $entityType->setEntityTypeId((int)$entityTypeId);
            } else {
                $entityType->setEntityTypeId((int)$existingType['entity_type_id']);
            }

            // Ensure entity table exists
            if (!$this->tableExists($entityType->getEntityTable())) {
                $this->createTableFromBlueprint($this->structureBuilder->buildEntityTable($entityType));
            }

            // Synchronize attributes
            $this->synchronizeAttributes($entityType);

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new SynchronizationException(
                "Failed to synchronize entity type '{$entityType->getCode()}': " . $e->getMessage(),
                "Schema synchronization failed",
                ['entity_type' => $entityType->getCode(), 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Synchronize all entity types
     */
    public function synchronizeAll(array $entityTypes): bool
    {
        foreach ($entityTypes as $entityType) {
            $this->synchronize($entityType);
        }
        return true;
    }

    /**
     * Check if synchronization is needed
     */
    public function needsSynchronization(EntityType $entityType): bool
    {
        // Check if entity type exists
        $existingType = $this->db->table('eav_entity_type')
            ->where('entity_code', $entityType->getCode())
            ->first();

        if (!$existingType) {
            return true;
        }

        // Check if entity table exists
        if (!$this->tableExists($entityType->getEntityTable())) {
            return true;
        }

        // Check if attributes need sync
        $existingAttributes = $this->db->table('eav_attribute')
            ->where('entity_type_id', $existingType['entity_type_id'])
            ->get();

        $existingCodes = array_column($existingAttributes, 'attribute_code');
        $configuredCodes = array_map(
            fn($attr) => $attr->getCode(),
            iterator_to_array($entityType->getAttributes())
        );

        return count(array_diff($configuredCodes, $existingCodes)) > 0;
    }

    /**
     * Get current schema version
     */
    public function getSchemaVersion(): string
    {
        return '2.0.0';
    }

    /**
     * Check if base schema is initialized
     */
    private function isInitialized(): bool
    {
        return $this->tableExists('eav_entity_type') &&
               $this->tableExists('eav_attribute') &&
               $this->tableExists('eav_value_varchar');
    }

    /**
     * Create entity type record
     */
    private function createEntityType(EntityType $entityType): string
    {
        return $this->db->table('eav_entity_type')->insert($entityType->toArray());
    }

    /**
     * Synchronize attributes for entity type
     */
    private function synchronizeAttributes(EntityType $entityType): void
    {
        $entityTypeId = $entityType->getEntityTypeId();

        // Get existing attributes
        $existingAttributes = $this->db->table('eav_attribute')
            ->where('entity_type_id', $entityTypeId)
            ->get();

        $existingMap = [];
        foreach ($existingAttributes as $attr) {
            $existingMap[$attr['attribute_code']] = $attr;
        }

        // Process configured attributes
        foreach ($entityType->getAttributes() as $attribute) {
            $code = $attribute->getCode();

            if (!isset($existingMap[$code])) {
                // Insert new attribute
                $data = array_merge(
                    ['entity_type_id' => $entityTypeId],
                    $attribute->toArray()
                );
                $attributeId = $this->db->table('eav_attribute')->insert($data);
                $attribute->setAttributeId((int)$attributeId);
            } else {
                // Update existing if changed
                $existing = $existingMap[$code];
                $attributeId = $existing['attribute_id'];
                $attribute->setAttributeId((int)$attributeId);

                if ($this->attributeChanged($attribute, $existing)) {
                    $this->db->table('eav_attribute')
                        ->where('attribute_id', $attributeId)
                        ->update($attribute->toArray());
                }
            }
        }
    }

    /**
     * Check if attribute definition has changed
     */
    private function attributeChanged(object $attribute, array $existing): bool
    {
        $fields = [
            'attribute_label', 'backend_type', 'frontend_type',
            'is_required', 'is_unique', 'is_searchable', 'is_filterable',
            'default_value', 'validation_rules', 'sort_order'
        ];

        $attrArray = $attribute->toArray();

        foreach ($fields as $field) {
            if (($attrArray[$field] ?? null) != ($existing[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create table from Blueprint
     */
    private function createTableFromBlueprint($blueprint): void
    {
        $sql = $blueprint->toSql();
        $this->db->execute($sql);
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $result = $this->db->execute(
                "SHOW TABLES LIKE ?",
                [$tableName]
            )->fetch();
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
}
