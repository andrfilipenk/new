<?php
date_default_timezone_set('Europe/Berlin');
/** 
 * @var \Core\Mvc\Application $app 
*/
require '../app/bootstrap.php';
$app        = $di->get('\Core\Mvc\Application');
$app->run();