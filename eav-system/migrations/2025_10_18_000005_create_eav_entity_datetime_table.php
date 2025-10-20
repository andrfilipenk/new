<?php
// migrations/2025_10_18_000005_create_eav_entity_datetime_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_entity_datetime', function (Blueprint $table) {
            $table->id('value_id');
            $table->unsignedBigInteger('entity_id');
            $table->string('attribute_code', 100);
            $table->datetime('value');
            
            $table->index(['entity_id', 'attribute_code']);
            $table->index('attribute_code');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_entity_datetime');
    }
};
