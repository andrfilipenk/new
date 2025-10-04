<?php
// app/Admin/config.php
return [
    'routes' => [
        
        // Holiday Request routes
        '/admin/holidays' => [
            'module'     => 'Admin',
            'controller' => 'Holidays',
            'action'     => 'index'
        ],
        '/admin/holidays/create' => [
            'module'     => 'Admin',
            'controller' => 'Holidays',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/admin/holidays/manage/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Holidays',
            'action'     => 'manage',
            'method'     => ['GET', 'POST']
        ],
        '/admin/holidays/delete/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Holidays',
            'action'     => 'delete'
        ],
        '/admin/holidays/myrequests' => [
            'module'     => 'Admin',
            'controller' => 'Holidays',
            'action'     => 'myrequests'
        ],



        
    ],
];