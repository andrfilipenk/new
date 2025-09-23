<?php
// public/index.php
$app = require '../app/bootstrap.php';
$request = $app->getDI()->get('request');
$response = $app->handle($request);
$response->send();