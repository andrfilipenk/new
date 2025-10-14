<?php
// app/User/config.php
return [
    'provider' => [
        '\User\Provider\AuthServiceProvider',
    ],

    'navbar' => [
        [
            'label' => 'Dashboard',
            'icon' => 'calendar3',
            'url' => '/board'
        ],
        [
            'label' => 'Users',
            'icon' => 'people',
            'url'  => '/user',
        ],
        [
            'label' => 'Groups',
            'icon' => 'diagram-3',
            'url' => '/group',
        ],
        [
            'label' => 'Permissions',
            'icon' => 'ui-checks',
            'url' => '/permissions',
        ],
        [
            'label' => 'My Profile',
            'icon' => 'gear-fill',
            'url' => '/profile',
        ],
 
    ],

    'test' => [
        '/board'                => 'User.Board.index@GET',
        '/login'                => 'User.Auth.login@GET|POST',
        '/logout'               => 'User.Auth.logout@GET',
        '/my-profile'           => 'User.Profile.index@GET',
        '/edit-profile'         => 'User.Profile.edit@GET|POST',
        '/change-password'      => 'User.Profile.password@GET|POST',

        '/user-list'            => 'User.Account.list@GET',
        '/user-group'           => 'User.Group.list@GET',
        ''
    ],

    'routes' => [
        '/board' => [
            'module'     => 'User',
            'controller' => 'Board',
            'action'     => 'index',
            'method'     => ['GET']
        ],
        // Authentication routes 
        '/login' => [
            'module'     => 'User',
            'controller' => 'Auth',
            'action'     => 'login',
            'method'     => ['GET', 'POST']
        ],
        '/logout' => [
            'module'     => 'User',
            'controller' => 'Auth',
            'action'     => 'logout',
            'method'     => 'GET'
        ],

        // Profile routes
        '/profile' => [
            'module'     => 'User',
            'controller' => 'Profile',
            'action'     => 'index',
            'method'     => 'GET'
        ],
        '/profile/edit' => [
            'module'     => 'User',
            'controller' => 'Profile',
            'action'     => 'edit',
            'method'     => ['GET', 'POST']
        ],
        '/profile/password' => [
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
        '/group' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'index'
        ],
        '/group/create' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'create',
            'method'     => ['GET', 'POST']
        ],
        '/group/delete/{id}' => [
            'module'     => 'User',
            'controller' => 'Group',
            'action'     => 'delete',
            'method'     => ['GET', 'POST']
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