<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Roles Table
 */
class CreateRolesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('roles', function(Schema $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->text('description')->nullable();
            $table->integer('level')->default(0)->comment('Role hierarchy level');
            $table->timestamps();
            
            $table->unique(['slug']);
            $table->index(['level']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('roles');
    }
}
