<?php
// public/index.php

$app = require '../app/bootstrap.php';
$request = new \Core\Http\Request();
$response = $app->handle($request);
$response->send();