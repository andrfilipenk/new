<?php

/**
 * Framework Bootstrap File
 * 
 * Initializes the framework and prepares it for request handling
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load autoloader
require_once BASE_PATH . '/core/Autoloader.php';

// Register autoloader
Core\Autoloader::register();
Core\Autoloader::addNamespace('Core', BASE_PATH . '/core');
Core\Autoloader::addNamespace('App', BASE_PATH . '/app');
Core\Autoloader::addNamespace('Modules', BASE_PATH . '/modules');

// Load helper functions
require_once BASE_PATH . '/core/helpers.php';

// Initialize configuration
Core\Config\Config::initialize(BASE_PATH . '/config');

// Start session
Core\Session\SessionManager::start();

// Register error handler
Core\Error\ErrorHandler::register();

// Set timezone
date_default_timezone_set(Core\Config\Config::get('app.timezone', 'UTC'));

// Set error reporting based on environment
if (Core\Config\Config::get('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Return application instance (to be implemented)
return function() {
    // Application will be initialized here
    return [
        'config' => Core\Config\Config::class,
        'db' => Core\Database\DB::class,
        'request' => Core\Request::class,
        'response' => Core\Response::class,
    ];
};
