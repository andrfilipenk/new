<?php
// public/index.php

// Get application instance
$application = require '../app/bootstrap.php';

// Handle the request
$uri = str_replace("new/", "", $_SERVER['REQUEST_URI']);
$application->handle($uri);