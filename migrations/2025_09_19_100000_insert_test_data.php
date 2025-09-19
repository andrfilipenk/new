<?php
use Core\Database\Migration;

class InsertTestData extends Migration
{
    protected $userList = [
        // [user_id, name, email, password, kuhnle_id]
        [1, 'Andrej Filipenko', 'john.smith@email.com', 'pass123', 4521],
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

    protected $taskList = [
        // [created_by, assigned_to, title, begin_date, end_date, status, priority]
        [3, 1, 'Website Redesign', '2025-09-08', '2025-09-25', 'in-progress', 7],
        [5, 1, 'Database Optimization', '2025-09-06', '2025-09-20', 'done', 8],
        [7, 1, 'API Documentation', '2025-09-12', '2025-09-28', 'open', 5],
        [12, 1, 'Client Presentation', '2025-09-05', '2025-09-19', 'done', 9],
        [18, 1, 'Bug Fixing', '2025-09-15', '2025-09-26', 'in-progress', 6],
        [9, 1, 'Security Audit', '2025-09-10', '2025-09-30', 'open', 8],
        [14, 1, 'Content Migration', '2025-09-14', '2025-09-27', 'in-progress', 4],
        [20, 1, 'Training Materials', '2025-09-16', '2025-10-02', 'open', 3],
        [1, 4, 'Mobile App Development', '2025-09-09', '2025-10-05', 'in-progress', 9],
        [1, 7, 'Server Maintenance', '2025-09-07', '2025-09-18', 'done', 7],
        [1, 12, 'SEO Optimization', '2025-09-13', '2025-10-03', 'open', 6],
        [1, 3, 'Payment Integration', '2025-09-08', '2025-09-24', 'in-progress', 8],
        [1, 15, 'User Testing', '2025-09-17', '2025-10-01', 'open', 5],
        [1, 8, 'Email Campaign', '2025-09-11', '2025-09-26', 'in-progress', 4],
        [1, 19, 'Data Backup', '2025-09-06', '2025-09-17', 'done', 6],
        [1, 6, 'Dashboard Design', '2025-09-18', '2025-10-06', 'open', 7],
        [1, 11, 'Performance Testing', '2025-09-19', '2025-10-08', 'in-progress', 8],
        [1, 2, 'Documentation Update', '2025-09-05', '2025-09-22', 'done', 3],
        [1, 17, 'Social Media Strategy', '2025-09-20', '2025-10-09', 'open', 5],
        [1, 10, 'Customer Support', '2025-09-21', '2025-10-04', 'in-progress', 6],
        [1, 13, 'Bug Reports Analysis', '2025-09-22', '2025-10-07', 'open', 4],
        [1, 16, 'Feature Planning', '2025-09-23', '2025-10-10', 'in-progress', 9]
    ];

    protected $taskLogs = [
        // [task_id, content, created_at]
        [1, 'Andrej Filipenko created Website Redesign', '2025-09-08 09:15:00'],
        [1, 'Michael Brown assigned to Website Redesign', '2025-09-08 09:20:00'],
        [1, 'Michael Brown started working on Website Redesign', '2025-09-08 10:30:00'],
        [2, 'David Wilson created Database Optimization', '2025-09-06 08:45:00'],
        [2, 'Andrej Filipenko assigned to Database Optimization', '2025-09-06 08:50:00'],
        [2, 'Andrej Filipenko completed Database Optimization', '2025-09-20 16:20:00'],
        [3, 'Christopher Taylor created API Documentation', '2025-09-12 11:10:00'],
        [3, 'Andrej Filipenko assigned to API Documentation', '2025-09-12 11:15:00'],
        [4, 'Lisa Harris created Client Presentation', '2025-09-05 14:20:00'],
        [4, 'Andrej Filipenko assigned to Client Presentation', '2025-09-05 14:25:00'],
        [4, 'Andrej Filipenko submitted Client Presentation', '2025-09-19 15:40:00'],
        [5, 'Brian Robinson created Bug Fixing', '2025-09-15 10:05:00'],
        [5, 'Andrej Filipenko assigned to Bug Fixing', '2025-09-15 10:10:00'],
        [5, 'Andrej Filipenko began fixing bugs', '2025-09-15 13:30:00'],
        [6, 'Matthew Thomas created Security Audit', '2025-09-10 09:30:00'],
        [6, 'Andrej Filipenko assigned to Security Audit', '2025-09-10 09:35:00'],
        [7, 'Michelle Thompson created Content Migration', '2025-09-14 15:45:00'],
        [7, 'Andrej Filipenko assigned to Content Migration', '2025-09-14 15:50:00'],
        [7, 'Andrej Filipenko started content migration process', '2025-09-14 16:30:00'],
        [8, 'Rebecca Lewis created Training Materials', '2025-09-16 13:15:00'],
        [8, 'Andrej Filipenko assigned to Training Materials', '2025-09-16 13:20:00'],
        [9, 'Andrej Filipenko created Mobile App Development', '2025-09-09 08:00:00'],
        [9, 'Sarah Davis assigned to Mobile App Development', '2025-09-09 08:05:00'],
        [9, 'Sarah Davis began mobile app development', '2025-09-09 09:15:00'],
        [10, 'Andrej Filipenko created Server Maintenance', '2025-09-07 07:30:00'],
        [10, 'Christopher Taylor assigned to Server Maintenance', '2025-09-07 07:35:00'],
        [10, 'Christopher Taylor completed server maintenance', '2025-09-18 12:00:00'],
        [11, 'Andrej Filipenko created SEO Optimization', '2025-09-13 10:20:00'],
        [11, 'Lisa Harris assigned to SEO Optimization', '2025-09-13 10:25:00'],
        [12, 'Andrej Filipenko created Payment Integration', '2025-09-08 11:45:00'],
        [12, 'Michael Brown assigned to Payment Integration', '2025-09-08 11:50:00'],
        [12, 'Michael Brown started payment integration work', '2025-09-08 14:20:00'],
        [13, 'Andrej Filipenko created User Testing', '2025-09-17 14:10:00'],
        [13, 'Mark Garcia assigned to User Testing', '2025-09-17 14:15:00'],
        [14, 'Andrej Filipenko created Email Campaign', '2025-09-11 12:30:00'],
        [14, 'Jessica Anderson assigned to Email Campaign', '2025-09-11 12:35:00'],
        [14, 'Jessica Anderson launched email campaign', '2025-09-26 11:00:00'],
        [15, 'Andrej Filipenko created Data Backup', '2025-09-06 16:40:00'],
        [15, 'Timothy Rodriguez assigned to Data Backup', '2025-09-06 16:45:00'],
        [15, 'Timothy Rodriguez completed data backup', '2025-09-17 10:15:00'],
        [16, 'Andrej Filipenko created Dashboard Design', '2025-09-18 09:50:00'],
        [16, 'Jennifer Miller assigned to Dashboard Design', '2025-09-18 09:55:00'],
        [17, 'Andrej Filipenko created Performance Testing', '2025-09-19 13:25:00'],
        [17, 'Daniel White assigned to Performance Testing', '2025-09-19 13:30:00'],
        [17, 'Daniel White began performance testing', '2025-09-19 15:45:00'],
        [18, 'Andrej Filipenko created Documentation Update', '2025-09-05 15:10:00'],
        [18, 'Emma Johnson assigned to Documentation Update', '2025-09-05 15:15:00'],
        [18, 'Emma Johnson completed documentation update', '2025-09-22 14:30:00'],
        [19, 'Andrej Filipenko created Social Media Strategy', '2025-09-20 11:05:00'],
        [19, 'Brian Robinson assigned to Social Media Strategy', '2025-09-20 11:10:00'],
        [20, 'Andrej Filipenko created Customer Support', '2025-09-21 10:15:00'],
        [20, 'Amanda Jackson assigned to Customer Support', '2025-09-21 10:20:00'],
        [20, 'Amanda Jackson handled customer support tickets', '2025-09-21 14:00:00'],
        [21, 'Andrej Filipenko created Bug Reports Analysis', '2025-09-22 08:45:00'],
        [21, 'Kevin Martin assigned to Bug Reports Analysis', '2025-09-22 08:50:00'],
        [22, 'Andrej Filipenko created Feature Planning', '2025-09-23 09:30:00'],
        [22, 'Nancy Martinez assigned to Feature Planning', '2025-09-23 09:35:00'],
        [22, 'Nancy Martinez started feature planning session', '2025-09-23 11:20:00'],
        [1, 'Michael Brown updated design mockups for Website Redesign', '2025-09-09 14:25:00'],
        [1, 'Michael Brown submitted design review for Website Redesign', '2025-09-12 16:40:00'],
        [1, 'Andrej Filipenko approved design changes for Website Redesign', '2025-09-13 10:15:00'],
        [3, 'Andrej Filipenko requested additional API endpoints', '2025-09-15 11:30:00'],
        [3, 'Christopher Taylor added new API documentation sections', '2025-09-16 14:20:00'],
        [5, 'Andrej Filipenko reported new bugs found during testing', '2025-09-18 09:45:00'],
        [5, 'Brian Robinson fixed critical security vulnerability', '2025-09-19 15:10:00'],
        [6, 'Andrej Filipenko extended Security Audit deadline', '2025-09-20 12:00:00'],
        [7, 'Michelle Thompson migrated content to new server', '2025-09-18 10:30:00'],
        [7, 'Andrej Filipenko verified content migration success', '2025-09-19 14:15:00'],
        [8, 'Rebecca Lewis provided additional training resources', '2025-09-20 11:45:00'],
        [9, 'Sarah Davis completed mobile app prototype', '2025-09-20 16:30:00'],
        [9, 'Andrej Filipenko reviewed mobile app prototype', '2025-09-22 10:00:00'],
        [10, 'Christopher Taylor scheduled server maintenance window', '2025-09-10 08:30:00'],
        [11, 'Lisa Harris implemented SEO recommendations', '2025-09-20 13:15:00'],
        [12, 'Michael Brown integrated payment gateway successfully', '2025-09-18 15:45:00'],
        [12, 'Andrej Filipenko tested payment integration', '2025-09-19 11:20:00'],
        [13, 'Mark Garcia recruited user testing participants', '2025-09-22 12:30:00'],
        [14, 'Jessica Anderson designed email templates', '2025-09-15 14:50:00'],
        [15, 'Timothy Rodriguez verified backup integrity', '2025-09-15 09:00:00'],
        [16, 'Jennifer Miller presented dashboard design concepts', '2025-09-23 15:20:00'],
        [17, 'Daniel White identified performance bottlenecks', '2025-09-24 10:45:00'],
        [18, 'Emma Johnson organized documentation structure', '2025-09-12 13:40:00'],
        [19, 'Brian Robinson developed social media content calendar', '2025-09-25 14:10:00'],
        [20, 'Amanda Jackson resolved customer support issues', '2025-09-25 16:00:00'],
        [21, 'Kevin Martin categorized bug reports by priority', '2025-09-26 09:30:00'],
        [22, 'Nancy Martinez completed feature requirements document', '2025-09-28 11:15:00'],
        [1, 'Michael Brown marked Website Redesign as in-progress', '2025-09-15 08:45:00'],
        [2, 'David Wilson marked Database Optimization as done', '2025-09-20 16:20:00'],
        [3, 'Christopher Taylor updated API documentation status to open', '2025-09-14 10:00:00'],
        [4, 'Lisa Harris marked Client Presentation as completed', '2025-09-19 15:40:00'],
        [5, 'Brian Robinson changed Bug Fixing status to in-progress', '2025-09-16 09:15:00'],
        [6, 'Matthew Thomas set Security Audit status to open', '2025-09-11 14:20:00'],
        [7, 'Michelle Thompson updated Content Migration progress', '2025-09-17 11:30:00'],
        [8, 'Rebecca Lewis opened Training Materials task', '2025-09-17 08:00:00'],
        [9, 'Sarah Davis progressed Mobile App Development to in-progress', '2025-09-11 15:45:00'],
        [10, 'Christopher Taylor completed Server Maintenance successfully', '2025-09-18 12:00:00'],
        [11, 'Lisa Harris began SEO Optimization work', '2025-09-16 10:15:00'],
        [12, 'Michael Brown started Payment Integration development', '2025-09-09 13:20:00'],
        [13, 'Mark Garcia opened User Testing preparation', '2025-09-19 09:00:00'],
        [14, 'Jessica Anderson launched Email Campaign successfully', '2025-09-26 11:00:00'],
        [15, 'Timothy Rodriguez finished Data Backup procedure', '2025-09-17 10:15:00'],
        [16, 'Jennifer Miller started Dashboard Design work', '2025-09-19 14:30:00']
    ];

    public function saveTestData($table, $keys, $array)
    {
        $db = self::db()->table($table);
        foreach ($array as $row) {
            $data = array_combine($keys, $row);
            $db->insert($data);
        }
        return $this;
    }

    public function up()
    {
        $this->saveTestData(
            'users', 
            ['user_id', 'name', 'email', 'password', 'kuhnle_id'], 
            $this->userList
        );

        $this->saveTestData(
            'tasks', 
            ['created_by', 'assigned_to', 'title', 'begin_date', 'end_date', 'status', 'priority'], 
            $this->taskList
        );

        $this->saveTestData(
            'tasks_log', 
            ['task_id', 'content', 'created_at'], 
            $this->taskLogs
        );
    }

    public function down()
    {
        #$this->dropTable('holidays');
    }
}