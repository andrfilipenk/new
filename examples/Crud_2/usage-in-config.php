<?php
// app/config.php - Updated routes section
$config = [
    'admin' => [
        'routes' => array_merge(
            \Core\Crud\RouteGenerator::crud('admin/users', 'Module\Admin\Controller\User'),
            \Core\Crud\RouteGenerator::crud('admin/tasks', 'Module\Admin\Controller\Task'),
            [
                // Additional custom routes
                '/admin/dashboard' => [
                    'controller' => 'Module\Admin\Controller\Dashboard',
                    'action' => 'index'
                ]
            ]
        )
    ]
];