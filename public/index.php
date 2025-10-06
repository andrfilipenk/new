<?php
// public/index.php
date_default_timezone_set('Europe/Berlin');
/** 
 * @var \Core\Mvc\App $app 
*/
require '../app/bootstrap.php';
$app        = $di->get('\Core\Mvc\App');
$app->run();