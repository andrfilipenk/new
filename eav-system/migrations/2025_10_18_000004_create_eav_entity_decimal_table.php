<?php
// migrations/2025_10_18_000004_create_eav_entity_decimal_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateEavEntityDecimalTable extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_entity_decimal', function (Blueprint $table) {
            $table->id('value_id');
            $table->bigo('entity_id');
            $table->string('attribute_code', 100);
            $table->decimal('value', 12, 4);
            
            $table->index(['entity_id', 'attribute_code']);
            $table->index('attribute_code');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_entity_decimal');
    }
};
