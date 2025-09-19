<?php
use Core\Database\Blueprint;
use Core\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('users', function($table) {
            /** @var Blueprint $table */
            $table->id('user_id');
            $table->string('name', 32);
            $table->string('email', 64);
            $table->integer('kuhnle_id', 4);
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}