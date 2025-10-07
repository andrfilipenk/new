<?php
// app/bootstrap.php
define('APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('APP_DIR', dirname($_SERVER['SCRIPT_NAME'],2));

// Register autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    foreach (['_', ''] as $sep) {
        $file = APP_PATH . $sep . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

// Load configuration
$config = require APP_PATH . 'config.php';
$di = new \Core\Di\Container();
$di->set('config', fn() => $config);
$di->set('eventsManager', '\Core\Events\Manager');
$di->set('validator', '\Core\Validation\Validator');
$di->set('db', fn() => new \Core\Database\Database);
$di->set('url', '\Core\Utils\Url');
$di->set('view', '\Core\Mvc\View');

$di->set('request', fn() => \Core\Http\Request::capture());
$di->set('response', fn() => \Core\Http\Response::create());

$di->set('migrationRepository', fn() => new \Core\Database\MigrationRepository);
$di->set('migrator', fn() => new \Core\Database\Migrator);

$di->set('logger', fn() => new \Core\Logging\Logger());
$di->set('exceptionHandler', fn() => new \Core\Exception\ExceptionHandler());

// Set global exception and error handlers
set_exception_handler(function (Throwable $e) use ($di) {
    /** @var \Core\Http\Response $response */
    $response = $di->get('exceptionHandler')->handle($e);
    $response->send();
});

set_error_handler(function ($severity, $message, $file, $line) use ($di) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});