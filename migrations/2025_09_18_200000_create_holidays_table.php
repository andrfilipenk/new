<?php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateHolidaysTable extends Migration
{
    public function up()
    {
        $this->createTable('holidays', function($table) {
            /** @var Blueprint $table */
            $table->id();
            $table->date('date_on');
            $table->string('title', 32);
        });

        $holidays = [
            '2025-01-01' => 'Neues Jahr',
            '2025-04-18' => 'Karlfreitag',
            '2025-04-21' => 'Ostermontag',
            '2025-05-01' => 'Tag der Arbeit',
            '2025-05-29' => 'Christi Himmelfahrt',
            '2025-06-09' => 'Pfingstmontag',
            '2025-10-03' => 'Tag der Deutschen Einheit',
            '2025-12-25' => '1. Weihnachtstag',
            '2025-12-26' => '2. Weihnachtstag'
        ];
        $query = self::db()->table('holidays');
        foreach ($holidays as $date => $title) {
            $query->insert([
                'date_on' => $date,
                'title'   => $title
            ]);
        }
    }

    public function down()
    {
        $this->dropTable('holidays');
    }
}