<?php
// app/config.php
return [
    'app' => [
        'name'              => 'My Application',
        'version'           => '1.0.0',
        'base'              => '/new/',
        'debug'             => true,
        'hash_algo'         => PASSWORD_BCRYPT,
        'module' => [
            'Main',
            'User',
            'Intern',
            'Project',
            'Crm',
        ],
    ],

    'logging' => [
        'file'              => APP_PATH . '../public/logs/app.log',
        // emergency, alert, critical, error, warning, notice, info, debug
        'level'             => 'error', 
        // Buffer logs to reduce I/O
        'buffer_size'       => 10, 
         // 5MB in bytes
        'rotation_size'     => 5242880
    ],

    'migrations' => [
        'path'              => APP_PATH . '../migrations',
        'table'             => 'migrations'
    ],

    'db' => [
        'driver'            => 'mysql',
        'host'              => 'localhost',
        'database'          => 'web',
        'username'          => 'root',
        'password'          => '',
        'charset'           => 'utf8mb4',
        'persistent'        => true, // for connection pooling
    ],

    'session' => [
        'driver'            => 'database', // 'database' or 'native'
        'table'             => 'sessions',
        'lifetime'          => 3600,
        'cookie_lifetime'   => 3600,
        'cookie_path'       => '/',
        'cookie_domain'     => '',
        'cookie_secure'     => false,
        'cookie_httponly'   => true,
        'cookie_samesite'   => 'Lax'
    ],

    'cookie' => [
        'expires'           => 30, // 30 days
        'path'              => '/',
        'domain'            => '',
        'secure'            => false,
        'httponly'          => true,
        'samesite'          => 'Lax'
    ],

    'view' => [
        'path'              => APP_PATH . 'views/',
        'layout'            => 'default'
    ],

    'acl' => [
        'public' => [
            ['Main', 'Home',  'index'],
            ['Main', 'Error', 'page'],
            ['User', 'Auth',  'login'],
        ]
    ],
];