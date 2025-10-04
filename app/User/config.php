<?php
// app/User/config.php
return [
    'provider' => [
        '\User\Provider\SessionServiceProvider',
        '\User\Provider\AclServiceProvider',
    ],

    'routes' => [
        // Authentication routes 
        '/user/login' => [
            'module'     => 'User',
            'controller' => 'Auth',
            'action'     => 'login',
            'method'     => ['GET', 'POST']
        ],
        '/user/logout' => [
            'module'     => 'User',
            'controller' => 'Auth',
            'action'     => 'logout',
            'method'     => 'GET'
        ],

        // Profile routes
        '/user/profile' => [
            'module'     => 'User',
            'controller' => 'Profile',
            'action'     => 'index',
            'method'     => 'GET'
        ],
        '/user/profile/edit' => [
            'module'     => 'User',
            'controller' => 'Profile',
            'action'     => 'edit',
            'method'     => ['GET', 'POST']
        ],
        '/user/profile/password' => [
            'module'     => 'User',
            'controller' => 'Profile',
            'action'     => 'password',
            'method'     => ['GET', 'POST']
        ],

        // User management routes 
        '/user' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'index'
        ],
        '/user/view/{id}' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'view'
        ],
        '/user/groups/{id}' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'groups',
            'method'     => ['GET', 'POST']
        ],
        '/user/create' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/user/edit/{id}' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'edit',
            'method'     => ['GET', 'POST']
        ],
        '/user/delete/{id}' => [
            'module'     => 'User',
            'controller' => 'User',
            'action'     => 'delete'
        ],

        // Group routes
        '/user/groups' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'index'
        ],
        '/user/groups/create' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/user/groups/delete/{id}' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'delete'
        ],

        // Access Control routes
        '/user/access' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'index'
        ],
        '/user/access/roles' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'roles'
        ],
        '/user/access/create-role' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'createRole'
        ],
        '/user/access/edit-role/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'editRole'
        ],
        '/user/access/delete-role/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'deleteRole'
        ],
        '/user/access/permissions' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'permissions'
        ],
        '/user/access/create-permission' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'createPermission'
        ],
        '/user/access/edit-permission/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'editPermission'
        ],
        '/user/access/delete-permission/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'deletePermission'
        ],
        '/user/access/user-roles' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'userRoles'
        ],
        '/user/access/manage-user-roles/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'manageUserRoles',
            'method'     => ['GET', 'POST']
        ],
        '/user/access/user-permissions' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'userPermissions',
            'method'     => ['GET', 'POST']
        ],
        '/user/access/manage-user-permissions/{id}' => [
            'module'     => 'User',
            'controller' => 'Access',
            'action'     => 'manageUserPermissions',
            'method'     => ['GET', 'POST']
        ],
    ],
];