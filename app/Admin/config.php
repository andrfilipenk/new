<?php
// app/Admin/config.php
return [
    'routes' => [
        
        '/admin/user' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'index'
        ],
        '/admin/user/view/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'view'
        ],
        '/admin/user/groups/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'groups',
            'method'     => ['GET', 'POST']
        ],
        '/admin/user/create' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/admin/user/edit/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'edit',
            'method'     => ['GET', 'POST']
        ],
        '/admin/user/delete/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'delete'
        ],


        '/admin/groups' => [
            'module'     => 'Admin',
            'controller' => 'Group',
            'action'     => 'index'
        ],
        '/admin/groups/create' => [
            'module'     => 'Admin',
            'controller' => 'Group',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/admin/groups/delete/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Group',
            'action'     => 'delete'
        ],



        
    ],
];