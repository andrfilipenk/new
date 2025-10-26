<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Media Table
 */
class CreateCmsMediaTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_media', function(Schema $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('filename', 255);
            $table->string('filepath', 500);
            $table->string('mime_type', 100);
            $table->string('file_extension', 10);
            $table->integer('file_size'); // in bytes
            $table->integer('width')->nullable(); // for images
            $table->integer('height')->nullable(); // for images
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by');
            $table->string('folder', 255)->default('/');
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['uploaded_by']);
            $table->index(['mime_type']);
            $table->index(['folder']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_media');
    }
}
