<?php
// app/Admin/config.php
return [
    'routes' => [
        
        '/admin/users' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'index'
        ],
        '/admin/user-create' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'create'
        ],
        '/admin/user-edit/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'edit'
        ],
        '/admin/user-delete/{id}' => [
            'module'     => 'Admin',
            'controller' => 'User',
            'action'     => 'delete'
        ],

        '/admin/tasks' => [
            'module'     => 'Admin',
            'controller' => 'Task',
            'action'     => 'index'
        ],
        '/admin/task-create' => [
            'module'     => 'Admin',
            'controller' => 'Task',
            'action'     => 'create'
        ],
        '/admin/task-edit/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Task',
            'action'     => 'edit'
        ],
        '/admin/task-delete/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Task',
            'action'     => 'delete'
        ],
    ],
];