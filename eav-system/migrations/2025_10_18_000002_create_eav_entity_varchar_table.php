<?php
// migrations/2025_10_18_000002_create_eav_entity_varchar_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateEavEntityVarcharTable extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_entity_varchar', function (Blueprint $table) {
            $table->id('value_id');
            $table->integer('entity_id');
            $table->string('attribute_code', 100);
            $table->string('value', 255);
            
            $table->index(['entity_id', 'attribute_code']);
            $table->index('attribute_code');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_entity_varchar');
    }
};
