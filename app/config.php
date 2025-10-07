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
            'Base',
            'User',
            'Admin',
            'Intern',
            'Crm'
        ],
    ],

    'logging' => [
        'file'              => APP_PATH . '../public/logs/app.log',
        'level'             => 'debug', // Options: emergency, alert, critical, error, warning, notice, info, debug
        'buffer_size'       => 10, // Buffer logs to reduce I/O
        'rotation_size'     => 5242880 // 5MB in bytes
    ],

    'data' => [
        'accounts' => [
            '1000' => 'user',
            '2000' => 'worker',
            '3000' => 'leader',
            '4000' => 'admin',
        ]
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

    'navigation' => [
        'Dashboard'         => '',
        'Users'             => 'admin/users',
        'Tasks'             => 'admin/tasks',
        'Log'               => 'admin/logs'
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
            ['base', 'dashboard',   'index'],
            ['base', 'error',       'denied'],
            ['base', 'error',       'notfound'],
            ['base', 'error',       'error'],
            ['user', 'auth',        'login'],
            ['user', 'auth',        'kuhnle'],
        ]
    ],
];