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
            $table->integer('custom_id');
            $table->string('password', 255)->nullable();
            $table->timestamps();
        });

        $this->createTable('user', function($table) {
            /** @var Blueprint $table */
            $table->id();
            $table->string('name', 32);
            $table->string('email', 64);
            $table->integer('custom_id');
            $table->string('password', 255)->nullable();
            $table->timestamps();
        });




        $users  = $this->getTestUserList();
        $config = $this->getDI()->get('config');
        $algo   = $config['app']['hash_algo'];
        foreach ($users as $i => $row) {
            $users[$i][3] = password_hash($row[3], $algo);
        }
        $this->insertDataArray('user', ['id', 'name', 'email', 'password', 'custom_id'], $users);
    }

    public function down()
    {
        $this->dropTable('user');;
    }

    protected function getTestUserList() {
        return [
            // [id, name, email, password, custom_id]
            [1, 'System User', 'system.user@email.com', 'user', 1000],
            [2, 'Worker Johnson', 'worker.johnson@email.com', 'worker', 2000],
            [3, 'Leader Brown', 'leader.b@email.com', 'leader', 3000],
            [4, 'Admin Davis', 'Admin.d@email.com', 'admin', 4000],
            [5, 'Andrej Filipenko', 'andrej.f@email.com', 'tester', 2020],
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