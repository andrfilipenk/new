<?php
// app/Core/Eav/Schema/StructureBuilder.php
namespace Core\Eav\Schema;

use Core\Database\Blueprint;
use Core\Eav\Model\EntityType;
use Core\Eav\Model\Attribute;

/**
 * Translates entity configurations into database table definitions
 */
class StructureBuilder
{
    /**
     * Build entity table definition
     */
    public function buildEntityTable(EntityType $entityType): Blueprint
    {
        $blueprint = new Blueprint($entityType->getEntityTable());
        
        // Primary key
        $blueprint->id('entity_id');
        
        // Entity type reference
        $blueprint->integer('entity_type_id')->unsigned();
        
        // Timestamps
        $blueprint->timestamps();
        
        // Index on entity_type_id
        $blueprint->index(['entity_type_id']);
        
        return $blueprint;
    }

    /**
     * Build entity type reference table
     */
    public function buildEntityTypeTable(): Blueprint
    {
        $blueprint = new Blueprint('eav_entity_type');
        
        $blueprint->id('entity_type_id');
        $blueprint->string('entity_code')->unique();
        $blueprint->string('entity_label');
        $blueprint->string('entity_table');
        $blueprint->string('storage_strategy', 32)->default('eav');
        $blueprint->timestamps();
        
        return $blueprint;
    }

    /**
     * Build attribute metadata table
     */
    public function buildAttributeTable(): Blueprint
    {
        $blueprint = new Blueprint('eav_attribute');
        
        $blueprint->id('attribute_id');
        $blueprint->integer('entity_type_id')->unsigned();
        $blueprint->string('attribute_code');
        $blueprint->string('attribute_label');
        $blueprint->string('backend_type', 32);
        $blueprint->string('frontend_type', 32);
        $blueprint->integer('is_required')->default(0);
        $blueprint->integer('is_unique')->default(0);
        $blueprint->integer('is_searchable')->default(0);
        $blueprint->integer('is_filterable')->default(0);
        $blueprint->text('default_value')->nullable();
        $blueprint->text('validation_rules')->nullable();
        $blueprint->integer('sort_order')->default(0);
        $blueprint->timestamps();
        
        // Unique constraint on entity_type_id + attribute_code
        $blueprint->index(['entity_type_id', 'attribute_code'], 'idx_entity_attr', 'UNIQUE');
        $blueprint->index(['entity_type_id']);
        
        return $blueprint;
    }

    /**
     * Build value table for specific backend type
     */
    public function buildValueTable(string $backendType): Blueprint
    {
        $tableName = "eav_value_{$backendType}";
        $blueprint = new Blueprint($tableName);
        
        $blueprint->id('value_id');
        $blueprint->integer('entity_type_id')->unsigned();
        $blueprint->integer('attribute_id')->unsigned();
        $blueprint->integer('entity_id')->unsigned();
        
        // Value column with appropriate type
        switch ($backendType) {
            case 'varchar':
                $blueprint->string('value')->nullable();
                break;
            case 'int':
                $blueprint->integer('value')->nullable();
                break;
            case 'decimal':
                $blueprint->decimal('value', 12, 4)->nullable();
                break;
            case 'datetime':
                $blueprint->timestamp('value')->nullable();
                break;
            case 'text':
                $blueprint->text('value')->nullable();
                break;
        }
        
        // Unique constraint on entity_type_id + attribute_id + entity_id
        $blueprint->index(
            ['entity_type_id', 'attribute_id', 'entity_id'],
            'idx_unique_entity_attr',
            'UNIQUE'
        );
        
        // Index for entity lookups
        $blueprint->index(['entity_id']);
        
        // Index for searchable attributes (except text)
        if ($backendType !== 'text') {
            $blueprint->index(['attribute_id', 'value']);
        }
        
        return $blueprint;
    }

    /**
     * Get all backend types that need value tables
     */
    public function getBackendTypes(): array
    {
        return ['varchar', 'int', 'decimal', 'datetime', 'text'];
    }

    /**
     * Build indexes for entity type
     */
    public function buildIndexes(EntityType $entityType, array $attributes): array
    {
        $indexes = [];
        
        foreach ($attributes as $attribute) {
            if ($attribute->isSearchable() || $attribute->isFilterable()) {
                $indexes[] = [
                    'table' => "eav_value_{$attribute->getBackendType()}",
                    'columns' => ['attribute_id', 'value'],
                    'name' => "idx_search_{$attribute->getCode()}"
                ];
            }
        }
        
        return $indexes;
    }
}
