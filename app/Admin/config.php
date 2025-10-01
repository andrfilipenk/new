<?php
// app/Admin/config.php
return [
    'routes' => [
        
        // User manager routes 
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


        // Group routes
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

        // Access Control routes
        '/admin/access' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'index'
        ],
        '/admin/access/roles' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'roles'
        ],
        '/admin/access/create-role' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'createRole'
        ],
        '/admin/access/edit-role/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'editRole'
        ],
        '/admin/access/delete-role/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'deleteRole'
        ],
        '/admin/access/permissions' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'permissions'
        ],
        '/admin/access/create-permission' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'createPermission'
        ],
        '/admin/access/edit-permission/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'editPermission'
        ],
        '/admin/access/delete-permission/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'deletePermission'
        ],
        '/admin/access/user-roles' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'userRoles'
        ],
        '/admin/access/manage-user-roles/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'manageUserRoles'
        ],
        '/admin/access/user-permissions' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'userPermissions'
        ],
        '/admin/access/manage-user-permissions/{id}' => [
            'module'     => 'Admin',
            'controller' => 'Access',
            'action'     => 'manageUserPermissions'
        ],



        
    ],
];