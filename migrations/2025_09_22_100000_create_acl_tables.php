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

        $userID = $this->insertData('acl_role', ['name' => 'user', 'display_name' => 'Benutzer']);
        $workerID = $this->insertData('acl_role', ['name' => 'worker', 'display_name' => 'Bearbeiter']);
        $leaderID = $this->insertData('acl_role', ['name' => 'leader', 'display_name' => 'Leiter']);
        $adminID = $this->insertData('acl_role', ['name' => 'admin', 'display_name' => 'Administrator']);

        // Create permission table
        $this->createTable('acl_permission', function(Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name', 150);
            $table->text('description')->nullable();
            $table->string('module', 16)->nullable(); // base
            $table->string('controller', 16)->nullable(); // task
            $table->string('action', 16)->nullable();   // edit
            $table->timestamps();
        });

        $taskListID = $this->insertData('acl_permission', ['name' => 'task-list', 'display_name' => 'List Tasks', 'module' => 'base', 'controller' => 'task', 'action' => 'list']);
        $taskViewID = $this->insertData('acl_permission', ['name' => 'task-view', 'display_name' => 'View Task', 'module' => 'base', 'controller' => 'task', 'action' => 'view']);
        $taskCreateID = $this->insertData('acl_permission', ['name' => 'task-create', 'display_name' => 'Create Task', 'module' => 'base', 'controller' => 'task', 'action' => 'create']);
        $taskEditID = $this->insertData('acl_permission', ['name' => 'task-edit', 'display_name' => 'Edit Task', 'module' => 'base', 'controller' => 'task', 'action' => 'edit']);
        $taskDeleteID = $this->insertData('acl_permission', ['name' => 'task-delete', 'display_name' => 'Delete Task', 'module' => 'base', 'controller' => 'task', 'action' => 'delete']);
        $taskMoveID = $this->insertData('acl_permission', ['name' => 'task-move', 'display_name' => 'Move Task', 'module' => 'base', 'controller' => 'task', 'action' => 'move']);
        $taskAssignID = $this->insertData('acl_permission', ['name' => 'task-assign', 'display_name' => 'Assign Task', 'module' => 'base', 'controller' => 'task', 'action' => 'assign']);

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