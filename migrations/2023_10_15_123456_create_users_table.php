<?php
// 2023_10_15_123456_create_users_table.php
use Core\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('users', function($table) {
            $table->id()->primary_key();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}