<?php

return [
    'module' => [
        'name' => 'Project',
        'namespace' => 'Project',
        'version' => '1.0.0',
        'description' => 'Enterprise project and order management system',
    ],

    'routes' => [
        '/projects' => [
            'module' => 'Project',
            'controller' => 'Project',
            'action' => 'index',
            'method' => ['GET'],
        ],


        [
            'path' => '/projects/create',
            'controller' => 'Project\Controller\ProjectController',
            'action' => 'create',
            'name' => 'projects.create',
            'methods' => ['GET', 'POST'],
        ],
        
        // Project Detail
        [
            'path' => '/projects/:id',
            'controller' => 'Project\Controller\ProjectController',
            'action' => 'detail',
            'name' => 'projects.detail',
            'methods' => ['GET'],
        ],
        [
            'path' => '/projects/:id/edit',
            'controller' => 'Project\Controller\ProjectController',
            'action' => 'update',
            'name' => 'projects.update',
            'methods' => ['GET', 'POST'],
        ],
        [
            'path' => '/projects/:id/delete',
            'controller' => 'Project\Controller\ProjectController',
            'action' => 'delete',
            'name' => 'projects.delete',
            'methods' => ['POST'],
        ],

        // Order Management
        [
            'path' => '/projects/:projectId/orders/create',
            'controller' => 'Project\Controller\OrderController',
            'action' => 'create',
            'name' => 'orders.create',
            'methods' => ['GET', 'POST'],
        ],
        [
            'path' => '/orders/:id',
            'controller' => 'Project\Controller\OrderController',
            'action' => 'detail',
            'name' => 'orders.detail',
            'methods' => ['GET'],
        ],
        [
            'path' => '/orders/:id/edit',
            'controller' => 'Project\Controller\OrderController',
            'action' => 'update',
            'name' => 'orders.update',
            'methods' => ['GET', 'POST'],
        ],
        [
            'path' => '/orders/:id/phases/:phaseId',
            'controller' => 'Project\Controller\OrderController',
            'action' => 'updatePhase',
            'name' => 'orders.update_phase',
            'methods' => ['POST'],
        ],
        [
            'path' => '/orders/:id/comments',
            'controller' => 'Project\Controller\OrderController',
            'action' => 'addComment',
            'name' => 'orders.add_comment',
            'methods' => ['POST'],
        ],

        // Position Management
        [
            'path' => '/orders/:orderId/positions/create',
            'controller' => 'Project\Controller\PositionController',
            'action' => 'create',
            'name' => 'positions.create',
            'methods' => ['GET', 'POST'],
        ],
        [
            'path' => '/positions/:id',
            'controller' => 'Project\Controller\PositionController',
            'action' => 'detail',
            'name' => 'positions.detail',
            'methods' => ['GET'],
        ],
        [
            'path' => '/positions/:id/edit',
            'controller' => 'Project\Controller\PositionController',
            'action' => 'update',
            'name' => 'positions.update',
            'methods' => ['GET', 'POST'],
        ],
        [
            'path' => '/positions/:id/delete',
            'controller' => 'Project\Controller\PositionController',
            'action' => 'delete',
            'name' => 'positions.delete',
            'methods' => ['POST'],
        ],
        [
            'path' => '/positions/:id/materials',
            'controller' => 'Project\Controller\PositionController',
            'action' => 'updateMaterial',
            'name' => 'positions.update_material',
            'methods' => ['POST'],
        ],

        // KPI Dashboard
        [
            'path' => '/dashboard/kpi',
            'controller' => 'Project\Controller\DashboardController',
            'action' => 'kpi',
            'name' => 'dashboard.kpi',
            'methods' => ['GET'],
        ],
        [
            'path' => '/dashboard/kpi/filter',
            'controller' => 'Project\Controller\DashboardController',
            'action' => 'filter',
            'name' => 'dashboard.filter',
            'methods' => ['POST'],
        ],
        [
            'path' => '/dashboard/kpi/export',
            'controller' => 'Project\Controller\DashboardController',
            'action' => 'export',
            'name' => 'dashboard.export',
            'methods' => ['GET'],
        ],
    ],

    'navigation' => [
        'sidebar' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard.kpi',
                'icon' => 'dashboard',
                'order' => 10,
            ],
            [
                'label' => 'Projects',
                'route' => 'projects.index',
                'icon' => 'folder',
                'order' => 20,
            ],
        ],
    ],

    'permissions' => [
        'project.view' => 'View projects',
        'project.create' => 'Create projects',
        'project.edit' => 'Edit projects',
        'project.delete' => 'Delete projects',
        'order.view' => 'View orders',
        'order.create' => 'Create orders',
        'order.edit' => 'Edit orders',
        'order.delete' => 'Delete orders',
        'position.view' => 'View positions',
        'position.create' => 'Create positions',
        'position.edit' => 'Edit positions',
        'position.delete' => 'Delete positions',
        'kpi.view' => 'View KPI dashboard',
    ],
];
