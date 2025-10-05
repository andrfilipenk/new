<?php
// app/Intern/config.php
return [
    'routes' => [
        
        '/tasklist' => [
            'module'     => 'Intern',
            'controller' => 'Task',
            'action'     => 'list',
            'method'     => 'GET'
        ],
        
        // Kanban Board Routes
        '/kanban' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'index',
            'method'     => 'GET'
        ],
        
        // Kanban API Routes
        '/kanban/board' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'board',
            'method'     => 'GET'
        ],
        
        '/kanban/task/create' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'createTask',
            'method'     => 'POST'
        ],
        
        '/kanban/task/{id:[0-9]+}/move' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'moveTask',
            'method'     => 'PUT'
        ],
        
        '/kanban/task/{id:[0-9]+}/details' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'taskDetails',
            'method'     => 'GET'
        ],
        
        '/kanban/task/{id:[0-9]+}/update' => [
            'module'     => 'Intern',
            'controller' => 'Kanban',
            'action'     => 'updateTask',
            'method'     => 'PUT'
        ],
    ],
];