<?php
// app/config.php

return [
    'app' => [
        'name' => 'My Application',
        'version' => '1.0.0'
    ],

    'db' => [
        'driver'        => 'mysql',
        'host'          => 'localhost',
        'database'      => 'web',
        'username'      => 'root',
        'password'      => '',
        'charset'       => 'utf8mb4',
        'persistent'    => true, // for connection pooling
    ],

    'session' => [
        'name' => 'MYAPP_SESSID',
        'lifetime' => 3600, // 1 hour
        'cookie_lifetime' => 3600,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => false,
        'cookie_httponly' => true
    ],

    'cookie' => [
        'expires' => 30, // 30 days
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ],

    'view' => [
        'path' => APP_PATH . 'views/',
        'layout' => 'default'
    ],
    
    'modules' => [
        'base' => [
            'routes' => [
                '/' => [
                    'controller' => 'Module\Base\Controller\Dashboard',
                    'action' => 'index',
                    'method'     => 'GET'
                ]
            ],
            'services' => [
                #'baseService' => 'Module\Base\Service\BaseService'
            ]
        ],
        'admin' => [
            'routes' => [
                '/admin/users' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => 'index'
                ],
                '/admin/user-create' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => 'create'
                ],
                '/admin/user-edit/{id}' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => 'edit'
                ],
                '/admin/user-delete/{id}' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => 'delete'
                ],
            ],
            'services' => [
                #'myappService' => 'Module\Myapp\Service\MyappService'
            ]
        ]
    ]
];