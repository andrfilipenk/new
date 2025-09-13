<?php

// 3. Base Controller Class
// app/Core/Controller.php
namespace Core;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Controller
{
    use Injectable, EventAware;
    
    // Common controller methods can be added here
}



// 4. Updated Bootstrap File
// app/bootstrap.php

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', BASE_PATH . 'app' . DIRECTORY_SEPARATOR);

// Register autoloader
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = APP_PATH . $classPath . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Load configuration
$config = require APP_PATH . 'config.php';

// Create application instance
$application = new \Core\Mvc\Application($config);

// Register modules
if (isset($config['modules'])) {
    $application->registerModules($config['modules']);
}

// return $application;





// 5. Updated Public Index File
// public/index.php

// Get application instance
$application = require APP_PATH . 'bootstrap.php';

// Handle the request
$application->handle($_SERVER['REQUEST_URI']);





// 6. Configuration Example
// app/config.php

return [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname' => 'myapp'
    ],
    
    'modules' => [
        'base' => [
            'routes' => [
                '/' => [
                    'namespace' => 'Module\Base',
                    'controller' => 'Module\Base\Controller\Index',
                    'action' => 'index'
                ]
            ],
            'services' => [
                'baseService' => 'Module\Base\Service\BaseService'
            ]
        ],
        'myapp' => [
            'routes' => [
                '/myapp/*' => [
                    'namespace' => 'Module\Myapp',
                    'controller' => 'Module\Myapp\Controller\Index',
                    'action' => 'index'
                ],
                '/myapp/{controller}/{action}' => [
                    'namespace' => 'Module\Myapp',
                    'controller' => 'Module\Myapp\Controller\{controller}',
                    'action' => '{action}'
                ]
            ],
            'services' => [
                'myappService' => 'Module\Myapp\Service\MyappService'
            ]
        ]
    ]
];





// Key Features of This Implementation:

/*
Key Features of This Implementation:

    Event Integration: Multiple event points for extensibility:

        core:beforeDispatch - Before any dispatch occurs

        core:beforeExecuteRoute - Before controller action execution

        core:afterExecuteRoute - After controller action execution

        core:afterDispatch - After complete dispatch

        application:beforeHandle - Before request handling

        application:afterHandle - After request handling

        application:beforeNotFound - When route is not found

        application:onException - When an exception occurs

    Module System: Organized structure for registering modules with routes and services

    Error Handling: Comprehensive exception handling with event hooks

    DI Integration: All components are managed through the dependency injection container

    Flexibility: Easy to extend with plugins and custom functionality*/