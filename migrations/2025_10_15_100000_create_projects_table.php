<?php

use Core\Database\Migration;

/**
 * Create projects table
 */
class CreateProjectsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('projects', function($table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('Planning');
            $table->decimal('budget', 15, 2)->default(0);
            $table->string('priority', 50)->default('Medium');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_id');
            $table->index('start_date');
            $table->index('end_date');
            
            $table->foreign('client_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $this->dropTable('projects');
    }
}
