<?php
// migrations/2025_01_15_000000_create_eav_base_structure.php
use Core\Database\Migration;

class CreateEavBaseStructure extends Migration
{
    public function up()
    {
        // Create entity type table
        $this->createTable('eav_entity_type', function($table) {
            $table->id('entity_type_id');
            $table->string('entity_code')->unique();
            $table->string('entity_label');
            $table->string('entity_table');
            $table->string('storage_strategy', 32)->default('eav');
            $table->timestamps();
        });

        // Create attribute metadata table
        $this->createTable('eav_attribute', function($table) {
            $table->id('attribute_id');
            $table->integer('entity_type_id')->unsigned();
            $table->string('attribute_code');
            $table->string('attribute_label');
            $table->string('backend_type', 32);
            $table->string('frontend_type', 32);
            $table->integer('is_required')->default(0);
            $table->integer('is_unique')->default(0);
            $table->integer('is_searchable')->default(0);
            $table->integer('is_filterable')->default(0);
            $table->text('default_value')->nullable();
            $table->text('validation_rules')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['entity_type_id', 'attribute_code'], 'idx_entity_attr', 'UNIQUE');
            $table->index(['entity_type_id']);
        });

        // Create value tables for each backend type
        $this->createValueTable('varchar', 'VARCHAR(255)');
        $this->createValueTable('int', 'INT');
        $this->createValueTable('decimal', 'DECIMAL(12,4)');
        $this->createValueTable('datetime', 'DATETIME');
        $this->createValueTable('text', 'TEXT');
    }

    private function createValueTable($type, $valueType)
    {
        $this->createTable("eav_value_{$type}", function($table) use ($valueType) {
            $table->id('value_id');
            $table->integer('entity_type_id')->unsigned();
            $table->integer('attribute_id')->unsigned();
            $table->integer('entity_id')->unsigned();
            
            if ($valueType === 'VARCHAR(255)') {
                $table->string('value')->nullable();
            } elseif ($valueType === 'INT') {
                $table->integer('value')->nullable();
            } elseif ($valueType === 'DECIMAL(12,4)') {
                $table->decimal('value', 12, 4)->nullable();
            } elseif ($valueType === 'DATETIME') {
                $table->timestamp('value')->nullable();
            } elseif ($valueType === 'TEXT') {
                $table->text('value')->nullable();
            }
            
            $table->index(['entity_type_id', 'attribute_id', 'entity_id'], 'idx_unique_entity_attr', 'UNIQUE');
            $table->index(['entity_id']);
            
            if ($valueType !== 'TEXT') {
                $table->index(['attribute_id', 'value']);
            }
        });
    }

    public function down()
    {
        $this->dropTable('eav_value_text');
        $this->dropTable('eav_value_datetime');
        $this->dropTable('eav_value_decimal');
        $this->dropTable('eav_value_int');
        $this->dropTable('eav_value_varchar');
        $this->dropTable('eav_attribute');
        $this->dropTable('eav_entity_type');
    }
}
