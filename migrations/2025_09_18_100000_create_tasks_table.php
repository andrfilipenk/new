<?php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateTasksTable extends Migration
{
    public function up()
    {
        $this->createTable('tasks', function($table) {
            /** @var Blueprint $table */
            $table->id();
            $table->integer('created_by')->unsigned();
            $table->integer('assigned_to')->unsigned();
            $table->string('title', 255);
            $table->date('begin_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->integer('priority')->default(1);
            $table->timestamps();
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->foreign('assigned_to')->references('user_id')->on('users');
        });

        $this->createTable('tasks_log', function($table) {
            /** @var Blueprint $table */
            $table->id();
            $table->integer('task_id')->unsigned();
            $table->string('content', 255);
            $table->timestamp('created_at')->default('CURRENT_TIMESTAMP');
            $table->foreign('task_id')->references('id')->on('tasks');
        });
    }

    public function down()
    {
        $this->dropTable('tasks_log');
        $this->dropTable('tasks');
    }
}