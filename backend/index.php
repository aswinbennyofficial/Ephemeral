<?php
require 'vendor/autoload.php';

use Slim\Factory\AppFactory;

// Create the application instance
$app = AppFactory::create();

// Middleware to handle CORS and JSON response types
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Content-Type', 'text/html');
});

// Load routes from routes.php
require 'routes.php';

// Run the application
$app->run();
