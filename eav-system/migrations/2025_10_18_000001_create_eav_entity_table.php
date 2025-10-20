<?php
// migrations/2025_10_18_000001_create_eav_entity_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_entity', function (Blueprint $table) {
            $table->id('entity_id');
            $table->string('entity_type', 100)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_entity');
    }
};
