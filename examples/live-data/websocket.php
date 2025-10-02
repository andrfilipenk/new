<?php
// bin/websocket.php
require_once __DIR__ . '/../app/bootstrap.php';
use Core\Utils\WebSocketServer;
use Core\Repository\PerformanceMetricsRepository;

$di = $bootstrap->getContainer();
$db = $di->get('db');
$repository = new PerformanceMetricsRepository($db);
$server = new WebSocketServer($repository);
$server->run();