<?php
// app/Base/config.php
return [
    'provider' => [

        '\Base\Provider\SessionServiceProvider',
        '\Base\Provider\ViewServiceProvider'
    ],

    'routes' => [
        '/' => [
            'module'     => 'Base',
            'controller' => 'Dashboard',
            'action'     => 'index',
            'method'     => 'GET'
        ],
        '/login' => [
            'module'     => 'Base',
            'controller' => 'Auth',
            'action'     => 'login',
            'method'     => ['GET', 'POST']
        ],
        '/kuhnle-{id}' => [
            'module'     => 'Base',
            'controller' => 'Auth',
            'action'     => 'kuhnle',
            'method'     => 'GET'
        ]
    ]
];