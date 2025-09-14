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

    'view' => [
        'path' => APP_PATH . 'views/',
        'layout' => 'default'
    ],
    
    'modules' => [
        'base' => [
            'routes' => [
                '/' => [
                    'controller' => 'Module\Base\Controller\Index',
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
                '/admin/' => [
                    'controller' => 'Module\Admin\Controller\Index',
                    'action' => 'index'
                ],
                '/admin/user-create' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => 'create' // create
                ],
                '/admin/user-{action}/{id}' => [
                    'controller' => 'Module\Admin\Controller\User',
                    'action' => '{action}' // get, update, delete
                ],
                
            ],
            'services' => [
                #'myappService' => 'Module\Myapp\Service\MyappService'
            ]
        ]
    ]
];