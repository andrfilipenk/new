<?php
// 2025_09_16_100000_create_posts_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->createTable('posts', function(Blueprint $table) {
            $table->id();
            $table->string('title', 200)->unique();
            $table->text('body');
            $table->integer('user_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        $this->dropTable('posts');
    }
}