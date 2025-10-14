<?php
// app/Main/config.php
return [

    'navbaraaa' => [
        [
            'label' => 'Tasks',
            'icon' => 'list-task',
            'url' => '/task'
        ],
    ],

    'routes' => [
        '/' => [
            'module'     => 'Main',
            'controller' => 'Home',
            'action'     => 'index',
            'method'     => ['GET','POST']
        ],
    ]
];