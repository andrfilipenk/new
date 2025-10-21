<?php
// migrations/2025_10_18_000003_create_eav_entity_int_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateEavEntityIntTable extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_entity_int', function (Blueprint $table) {
            $table->id('value_id');
            $table->integer('entity_id');
            $table->string('attribute_code', 100);
            $table->integer('value');
            
            $table->index(['entity_id', 'attribute_code']);
            $table->index('attribute_code');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_entity_int');
    }
};
