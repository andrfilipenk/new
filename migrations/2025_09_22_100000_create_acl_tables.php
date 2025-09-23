<?php
// migrations/2025_09_22_100000_create_acl_tables.php

use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateAclTables extends Migration
{
    public function up(): void
    {
        // Create role table
        $this->createTable('acl_role', function(Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create permission table
        $this->createTable('acl_permission', function(Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 150);
            $table->text('description')->nullable();
            $table->string('resource', 50)->nullable(); // e.g., 'users', 'posts', 'admin'
            $table->string('action', 50)->nullable();   // e.g., 'create', 'read', 'update', 'delete'
            $table->timestamps();
        });

        // Create role_permission junction table
        $this->createTable('acl_role_permission', function(Blueprint $table) {
            $table->id();
            $table->integer('role_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('acl_role');
            $table->foreign('permission_id')->references('id')->on('acl_permission');
        });

        // Create user_role junction table (assumes users table exists)
        $this->createTable('acl_user_role', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user');
            $table->foreign('role_id')->references('id')->on('acl_role');
        });

        // Create user_permission junction table (for direct user permissions)
        $this->createTable('acl_user_permission', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->integer('granted')->default(value: 1); // 1 = granted, 0 = denied
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user');  
            $table->foreign('permission_id')->references('id')->on('acl_permission');
        });
    }

    public function down(): void
    {
        $this->dropTable('acl_user_permission');
        $this->dropTable('acl_user_role');
        $this->dropTable('acl_role_permission');
        $this->dropTable('acl_permission');
        $this->dropTable('acl_role');
    }
}