<?php
use Core\Database\Blueprint;
use Core\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('user', function($table) {
            /** @var Blueprint $table */
            $table->id();
            $table->string('name', 32);
            $table->string('email', 64);
            $table->integer('kuhnle_id');
            $table->string('password')->nullable();
            $table->timestamps();
        });

        $this->insertDataArray(
            'user', 
            ['id', 'name', 'email', 'password', 'kuhnle_id'], 
            $this->getTestUserList()
        );
    }

    public function down()
    {
        $this->dropTable('user');
    }

    protected function getTestUserList() {
        return [
            // [id, name, email, password, kuhnle_id]
            [1, 'Andrej Filipenko', 'andrej.filipenko@email.com', 'pass123', 2020],
            [2, 'Emma Johnson', 'emma.johnson@email.com', 'secure', 7894],
            [3, 'Michael Brown', 'michael.b@email.com', 'mypass', 1236],
            [4, 'Sarah Davis', 'sarah.d@email.com', 'password', 9874],
            [5, 'David Wilson', 'david.w@email.com', 'david123', 6542],
            [6, 'Jennifer Miller', 'jennifer.m@email.com', 'jenny', 3217],
            [7, 'Christopher Taylor', 'chris.t@email.com', 'chrispass', 2589],
            [8, 'Jessica Anderson', 'jessica.a@email.com', 'jess123', 7456],
            [9, 'Matthew Thomas', 'matthew.t@email.com', 'mattpass', 6321],
            [10, 'Amanda Jackson', 'amanda.j@email.com', 'amanda', 4895],
            [11, 'Daniel White', 'daniel.w@email.com', 'danpass', 1678],
            [12, 'Lisa Harris', 'lisa.h@email.com', 'lisapass', 9234],
            [13, 'Kevin Martin', 'kevin.m@email.com', 'kevin123', 5768],
            [14, 'Michelle Thompson', 'michelle.t@email.com', 'michelle', 8342],
            [15, 'Mark Garcia', 'mark.g@email.com', 'markpass', 4198],
            [16, 'Nancy Martinez', 'nancy.m@email.com', 'nancy', 2673],
            [17, 'Brian Robinson', 'brian.r@email.com', 'brian123', 9851],
            [18, 'Stephanie Clark', 'stephanie.c@email.com', 'steph', 7324],
            [19, 'Timothy Rodriguez', 'timothy.r@email.com', 'timpass', 5489],
            [20, 'Rebecca Lewis', 'rebecca.l@email.com', 'rebecca', 3167]
        ];
    }
}