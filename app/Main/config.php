<?php
// app/Main/config.php
return [
    'provider' => [
        '\Core\Provider\CookieServiceProvider',
        '\Core\Provider\SessionServiceProvider',
        '\Main\Provider\ViewServiceProvider',
    ],

    'navbar' => [
        [
            'label' => 'Dashboard',
            'icon' => 'calendar3',
            'url' => '/board'
        ],
        [
            'label' => 'Tasks',
            'icon' => 'list-task',
            'url' => '/task'
        ],
    ],

    'routes' => [
        // '/' => 'Main.Hello.index',
        '/' => [
            'module'     => 'Main',
            'controller' => 'Hello',
            'action'     => 'index',
            'method'     => 'GET'
        ],
    ]
];