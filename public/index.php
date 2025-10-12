<?php
// public/index.php
date_default_timezone_set('Europe/Berlin');

require '../app/bootstrap.php';
/** @var \Core\Mvc\App $app */
$app        = $di->get('\Core\Mvc\App');
$app->setDI($di);
$app->run();