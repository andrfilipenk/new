<?php
use Core\Database\Blueprint;
use Core\Database\Migration;

class CreateUserHolidayRequestTable extends Migration
{
    public function up()
    {
        $this->createTable('user_holiday_request', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->date('begin_date');
            $table->date('end_date');
            $table->timestamps();
            $table->integer('granted')->default(0);

            $table->foreign('user_id')->references('id')->on('user');
        });
    }

    public function down()
    {
        $this->dropTable('user_holiday_request');
    }
}