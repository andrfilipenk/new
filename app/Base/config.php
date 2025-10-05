<?php
// app/Base/config.php
return [
    'provider' => [
        '\Base\Provider\CookieServiceProvider',
        '\Base\Provider\ViewServiceProvider'
    ],

    'routes' => [
        '/' => [
            'module'     => 'Base',
            'controller' => 'Dashboard',
            'action'     => 'index',
            'method'     => 'GET'
        ],
    ]
];