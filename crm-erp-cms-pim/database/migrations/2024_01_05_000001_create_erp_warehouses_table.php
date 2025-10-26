<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Warehouses Table
 */
class CreateErpWarehousesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_warehouses', function(Schema $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->index(['code']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_warehouses');
    }
}
