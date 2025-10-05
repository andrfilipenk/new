<?php
use Core\Database\Migration;
use Core\Database\Blueprint;

class UpdateTasksForKanban extends Migration
{
    public function up()
    {
        // Add position field to task table
        $this->alterTable('task', function($table) {
            /** @var Blueprint $table */
            $table->integer('position')->default(0)->after('priority_id');
            $table->text('description')->nullable()->after('title');
        });

        // Add position and is_active fields to task_status table
        $this->alterTable('task_status', function($table) {
            /** @var Blueprint $table */
            $table->integer('position')->default(0)->after('color');
            $table->boolean('is_active')->default(true)->after('position');
        });

        // Update existing task_status records with position and is_active
        $this->updateData('task_status', 
            ['id' => 1], 
            ['position' => 0, 'is_active' => true]
        );
        $this->updateData('task_status', 
            ['id' => 2], 
            ['position' => 1, 'is_active' => true]
        );
        $this->updateData('task_status', 
            ['id' => 3], 
            ['position' => 2, 'is_active' => true]
        );
        $this->updateData('task_status', 
            ['id' => 4], 
            ['position' => 3, 'is_active' => true]
        );

        // Set initial positions for existing tasks based on their order
        $this->executeSQL("
            UPDATE task 
            SET position = (
                SELECT COUNT(*) 
                FROM (SELECT * FROM task) t2 
                WHERE t2.status_id = task.status_id 
                AND t2.id <= task.id
            )
        ");

        // Add index for better kanban query performance
        $this->addIndex('task', ['status_id', 'position'], 'idx_task_status_position');
        $this->addIndex('task_status', ['position'], 'idx_task_status_position');

        // Enhance task_log table for kanban logging
        $this->alterTable('task_log', function($table) {
            /** @var Blueprint $table */
            $table->integer('user_id')->unsigned()->nullable()->after('task_id');
            $table->string('log_type', 32)->default('general')->after('content');
            $table->text('metadata')->nullable()->after('log_type');
            
            $table->foreign('user_id')->references('id')->on('user');
        });
    }

    public function down()
    {
        // Remove indexes
        $this->dropIndex('task', 'idx_task_status_position');
        $this->dropIndex('task_status', 'idx_task_status_position');

        // Remove columns from task table
        $this->alterTable('task', function($table) {
            /** @var Blueprint $table */
            $table->dropColumn('position');
            $table->dropColumn('description');
        });

        // Remove columns from task_status table
        $this->alterTable('task_status', function($table) {
            /** @var Blueprint $table */
            $table->dropColumn('position');
            $table->dropColumn('is_active');
        });

        // Remove enhanced task_log columns
        $this->alterTable('task_log', function($table) {
            /** @var Blueprint $table */
            $table->dropForeign('user_id');
            $table->dropColumn('user_id');
            $table->dropColumn('log_type');
            $table->dropColumn('metadata');
        });
    }
}