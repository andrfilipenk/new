<?php
// public/index.php
$dir = dirname(__DIR__);
define('BASE_PATH', substr($dir, strrpos($dir, DIRECTORY_SEPARATOR) + 1));

$app = require '../app/bootstrap.php';
$request = new \Core\Http\Request();
$response = $app->handle($request);
$response->send();