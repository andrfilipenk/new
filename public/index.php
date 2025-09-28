<?php
// public/index.php
require '../app/bootstrap.php';
/** 
 * @var \Core\Mvc\Application $app 
 * @var \Core\Mvc\Dispatcher $dispatcher
 * @var \Core\Mvc\View $view
 * @var \Core\Mvc\Router $router
 * */
$app        = $di->get('\Core\Mvc\Application');
$view       = $di->get('\Core\Mvc\View');
$router     = $di->get('\Core\Mvc\Router');
$dispatcher = $di->get('\Core\Mvc\Dispatcher');

$route      = $router->match();
$handle     = $dispatcher->handle($route);
$result     = $view->render($handle);
$app->run(request: $request, $di);