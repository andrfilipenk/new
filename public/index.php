<?php
// public/index.php

// Bootstrap the application and get the DI container
$app = require '../app/bootstrap.php';

// Create a request object from globals
$request = new \Core\Http\Request();

// Handle the request to get a response object
$response = $app->handle($request);

// Send the final response to the browser
$response->send();