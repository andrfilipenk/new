<?php
use Core\Database\Migration;

class CreateTasksTable extends Migration
{
    public function up()
    {
        $this->createTable('tasks', function($table) {
            $table->id('task_id');
            $table->integer('created_by')->unsigned();
            $table->integer('assigned_to')->unsigned();
            $table->string('title', 255);
            $table->date('created_date');
            $table->date('begin_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('open');
            $table->integer('priority')->default(1);
            $table->timestamps();
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->foreign('assigned_to')->references('user_id')->on('users');
        });
    }

    public function down()
    {
        $this->dropTable('tasks');
    }
}