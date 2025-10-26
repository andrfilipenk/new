<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Price Lists Table
 */
class CreatePimPriceListsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_price_lists', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            
            $table->index(['code']);
            $table->index(['is_active']);
            $table->index(['valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_price_lists');
    }
}
