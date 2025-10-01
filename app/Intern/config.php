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
        '/taskboard' => [
            'module'     => 'Intern',
            'controller' => 'Task',
            'action'     => 'board',
            'method'     => 'GET'
        ],
    ],
];