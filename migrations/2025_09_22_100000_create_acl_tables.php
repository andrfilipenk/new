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
            $table->string('module', 16)->nullable(); // base
            $table->string('controller', 16)->nullable(); // task
            $table->string('action', 16)->nullable();   // edit
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

        $sql = "INSERT INTO `acl_role` VALUES(1, 'user', 'Benutzer', 'Einfacher Nutzer der bestimmte Bereiche nur sehen kann.', '2025-09-24 20:03:53', '2025-09-24 20:03:53');
            INSERT INTO `acl_role` VALUES(2, 'worker', 'Mitarbeiter', 'Internet Mitarbeiter des Betriebes', '2025-09-24 20:04:31', '2025-09-24 20:04:31');
            INSERT INTO `acl_role` VALUES(3, 'leader', 'Abteilungsleiter', 'Leiter Produktion, Montage sowie Transport', '2025-09-24 20:04:52', '2025-09-24 20:04:52');
            INSERT INTO `acl_role` VALUES(4, 'admin', 'Administrator', NULL, '2025-09-24 20:05:22', '2025-09-24 20:05:22');
            INSERT INTO `acl_role` VALUES(5, 'master', 'Master', NULL, '2025-09-24 20:05:22', '2025-09-24 20:05:22');

            INSERT INTO `acl_permission` VALUES(1, 'task-list', 'List Tasks', NULL, 'base', 'task', 'list', '2025-09-24 20:07:29', '2025-09-24 20:07:29');
            INSERT INTO `acl_permission` VALUES(2, 'task-view', 'View Task Details', NULL, 'base', 'task', 'view', '2025-09-24 20:08:06', '2025-09-24 20:08:06');
            INSERT INTO `acl_permission` VALUES(3, 'task-create', 'Create new Task', NULL, 'base', 'task', 'create', '2025-09-24 20:08:40', '2025-09-24 20:08:40');
            INSERT INTO `acl_permission` VALUES(4, 'task-edit', 'Edit Task', NULL, 'base', 'task', 'edit', '2025-09-24 20:09:50', '2025-09-24 20:09:50');
            INSERT INTO `acl_permission` VALUES(5, 'task-delete', 'Delete Task', NULL, 'base', 'task', 'delete', '2025-09-24 20:10:38', '2025-09-24 20:10:38');
            INSERT INTO `acl_permission` VALUES(6, 'task-status', 'Change Task Status', NULL, 'base', 'task', 'status', '2025-09-24 20:11:40', '2025-09-24 20:11:40');
            INSERT INTO `acl_permission` VALUES(7, 'task-assign', 'Assign Task', NULL, 'base', 'task', 'assign', '2025-09-24 20:12:25', '2025-09-24 20:12:25');
            INSERT INTO `acl_permission` VALUES(8, 'task-comment', 'Task comments', NULL, 'base', 'task', 'comment', '2025-09-24 20:12:25', '2025-09-24 20:12:25');
            
            INSERT INTO `acl_user_role` VALUES(1, 1, 1, '2025-09-24 22:15:08', '2025-09-24 22:15:08');
            INSERT INTO `acl_user_role` VALUES(2, 2, 2, '2025-09-24 22:15:21', '2025-09-24 22:15:21');
            INSERT INTO `acl_user_role` VALUES(3, 3, 3, '2025-09-24 22:15:33', '2025-09-24 22:15:33');
            INSERT INTO `acl_user_role` VALUES(4, 4, 4, '2025-09-24 22:16:00', '2025-09-24 22:16:00');
            INSERT INTO `acl_user_role` VALUES(5, 5, 5, '2025-09-24 22:17:34', '2025-09-24 22:17:34');";

        $sql = str_replace(["\r", "\n"], "", $sql);
        $this->db()->getPdo()->exec($sql);
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