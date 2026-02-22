<?php

// Start session
session_start();

// Autoload dependencies first
require_once __DIR__ . '/vendor/autoload.php';

// Load helpers
require_once __DIR__ . '/app/helpers.php';

// Load environment variables
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Set error reporting
if (env('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load routes
$router = require_once __DIR__ . '/app/routes.php';

// Resolve and execute the route
$router->resolve();
