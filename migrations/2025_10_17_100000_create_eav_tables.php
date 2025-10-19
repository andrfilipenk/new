<?php
// migrations/2025_10_17_100000_create_eav_tables.php

use Core\Database\Migration;
use Core\Database\Blueprint;

return new class extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Create eav_entity_types table
        $this->createTable('eav_entity_types', function(Blueprint $table) {
            $table->id();
            $table->string('entity_type_code', 100)->unique();
            $table->string('entity_type_name', 255);
            $table->text('description')->nullable();
            $table->string('entity_table', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('entity_type_code');
        });

        // Create eav_attributes table
        $this->createTable('eav_attributes', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_type_id')->constrained('eav_entity_types')->onDelete('cascade');
            $table->string('attribute_code', 100);
            $table->string('attribute_name', 255);
            $table->enum('backend_type', ['varchar', 'int', 'decimal', 'text', 'datetime']);
            $table->enum('frontend_input', ['text', 'textarea', 'select', 'multiselect', 'date', 'datetime', 'boolean', 'number']);
            $table->string('source_model', 255)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('default_value', 255)->nullable();
            $table->text('validation_rules')->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->unique(['entity_type_id', 'attribute_code']);
            $table->index('attribute_code');
            $table->index('backend_type');
            $table->index(['entity_type_id', 'is_searchable']);
        });

        // Create eav_entities table
        $this->createTable('eav_entities', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_type_id')->constrained('eav_entity_types')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('eav_entities')->onDelete('set null');
            $table->string('entity_code', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('entity_type_id');
            $table->index(['entity_type_id', 'entity_code']);
            $table->index('parent_id');
            $table->index('is_active');
        });

        // Create eav_values_varchar table
        $this->createTable('eav_values_varchar', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('eav_entities')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->string('value', 255);
            
            $table->unique(['entity_id', 'attribute_id']);
            $table->index('attribute_id');
            $table->index('value');
        });

        // Create eav_values_int table
        $this->createTable('eav_values_int', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('eav_entities')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->integer('value');
            
            $table->unique(['entity_id', 'attribute_id']);
            $table->index('attribute_id');
            $table->index('value');
        });

        // Create eav_values_decimal table
        $this->createTable('eav_values_decimal', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('eav_entities')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->decimal('value', 12, 4);
            
            $table->unique(['entity_id', 'attribute_id']);
            $table->index('attribute_id');
            $table->index('value');
        });

        // Create eav_values_text table
        $this->createTable('eav_values_text', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('eav_entities')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->text('value');
            
            $table->unique(['entity_id', 'attribute_id']);
            $table->index('attribute_id');
        });

        // Create eav_values_datetime table
        $this->createTable('eav_values_datetime', function(Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('eav_entities')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->datetime('value');
            
            $table->unique(['entity_id', 'attribute_id']);
            $table->index('attribute_id');
            $table->index('value');
        });

        // Create eav_attribute_options table for select/multiselect attributes
        $this->createTable('eav_attribute_options', function(Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('eav_attributes')->onDelete('cascade');
            $table->string('option_value', 255);
            $table->string('option_label', 255);
            $table->integer('sort_order')->default(0);
            
            $table->index('attribute_id');
            $table->index('sort_order');
        });

        // Create eav_entity_cache table for performance
        $this->createTable('eav_entity_cache', function(Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->longText('cache_value');
            $table->integer('ttl')->default(3600);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index('cache_key');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('eav_entity_cache');
        $this->dropTable('eav_attribute_options');
        $this->dropTable('eav_values_datetime');
        $this->dropTable('eav_values_text');
        $this->dropTable('eav_values_decimal');
        $this->dropTable('eav_values_int');
        $this->dropTable('eav_values_varchar');
        $this->dropTable('eav_entities');
        $this->dropTable('eav_attributes');
        $this->dropTable('eav_entity_types');
    }
};
