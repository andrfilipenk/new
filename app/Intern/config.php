<?php
// app/Intern/config.php
return [
    'routes' => [
        
        '/tasklist' => [
            'module'     => 'Intern',
            'controller' => 'Task',
            'action'     => 'list',
            'method'     => 'GET'
        ],

        '/add-task' => [
            'module'     => 'Intern',
            'controller' => 'Task',
            'action'     => 'add',
            'method'     => 'POST'
        ]
    ],
];