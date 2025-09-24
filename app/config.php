<?php
// app/config.php

return [
    'app' => [
        'name'              => 'My Application',
        'version'           => '1.0.0',
        'base'              => '/new/',
        'debug'             => true,
        'hash_algo'         => PASSWORD_BCRYPT
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
        'allowed' => [
            'guest.base.auth.login',
            'guest.base.auth.kuhnle',
            'guest.base.error.notfound',
            'guest.base.error.denied',
        ],
        'denied' => [
            'module'     => 'base',
            'controller' => 'error',
            'action'     => 'denied',
        ]
    ],
    
    'modules' => [
        'base' => [
            'routes' => [
                '/' => [
                    'module'     => 'base',
                    'controller' => 'dashboard',
                    'action'     => 'index',
                    'method'     => 'GET'
                ],
                '/login' => [
                    'module'     => 'base',
                    'controller' => 'auth',
                    'action'     => 'login',
                    'method'     => ['GET', 'POST']
                ],
                '/kuhnle-{id}' => [
                    'module'     => 'base',
                    'controller' => 'auth',
                    'action'     => 'kuhnle',
                    'method'     => 'GET'
                ],
            ],
            'services' => [
                #'baseService' => 'Module\Base\Service\BaseService'
            ]
        ],
        'admin' => [
            'routes' => [
                // user management routes
                '/admin/users' => [
                    'module'     => 'admin',
                    'controller' => 'user',
                    'action' => 'index'
                ],
                '/admin/user-create' => [
                    'module'     => 'admin',
                    'controller' => 'user',
                    'action'     => 'create'
                ],
                '/admin/user-edit/{id}' => [
                    'module'     => 'admin',
                    'controller' => 'user',
                    'action'     => 'edit'
                ],
                '/admin/user-delete/{id}' => [
                    'module'     => 'admin',
                    'controller' => 'user',
                    'action'     => 'delete'
                ],

                // task routes
                '/admin/tasks' => [
                    'module'     => 'admin',
                    'controller' => 'task',
                    'action'     => 'index'
                ],
                '/admin/task-create' => [
                    'module'     => 'admin',
                    'controller' => 'task',
                    'action'     => 'create'
                ],
                '/admin/task-edit/{id}' => [
                    'module'     => 'admin',
                    'controller' => 'task',
                    'action'     => 'edit'
                ],
                '/admin/task-delete/{id}' => [
                    'module'     => 'admin',
                    'controller' => 'task',
                    'action'     => 'delete'
                ],
            ],
            'services' => [
                #'myappService' => 'Module\Myapp\Service\MyappService'
            ]
        ]
    ]
];