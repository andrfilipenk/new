<?php
// migrations/2025_09_023_000000_create_sessions_table.php

use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateSessionsTable extends Migration
{
    public function up()
    {
        $this->createTable('sessions', function(Blueprint $table) {
            /** @var Blueprint $table */
            $table->string('id', 128)->primary();
            $table->text('data');
            $table->integer('expires_at');
            $table->integer('last_activity');
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
        });
    }

    public function down()
    {
        $this->dropTable('sessions');
    }
}