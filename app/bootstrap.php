<?php
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', BASE_PATH . 'app' . DIRECTORY_SEPARATOR);

// Register autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = APP_PATH . $class . '.php';
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

return $application;
