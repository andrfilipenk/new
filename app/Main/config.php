<?php
// app/Main/config.php
return [
    'provider' => [
        '\Core\Provider\CookieServiceProvider',
        '\Core\Provider\SessionServiceProvider',
        '\Main\Provider\ViewServiceProvider',
    ],

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