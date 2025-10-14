<?php
// public/index.php
date_default_timezone_set('Europe/Berlin');

require '../bootstrap.php';
/** @var \Core\Mvc\App $app */

$di->set('app', '\Core\Mvc\App');
$app = $di->get('app');
$app->setDI($di);
$app->addMiddleware(\Core\Mvc\Middleware\ErrorMiddleware::class); // Optional
$app->run();