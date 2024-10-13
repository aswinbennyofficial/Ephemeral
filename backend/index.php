<?php
require 'vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Middleware to handle CORS and JSON response types
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Content-Type', 'text/html');
});

// Route for landing page
$app->get('/', function ($request, $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/landing.html'));
    return $response;
});

// Route for dashboard page with path parameter
$app->get('/dashboard/{param}', function ($request, $response, $args) {
    $param = $args['param'];
    // You might want to do something with $param
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/dashboard.html'));
    return $response;
});

// Route for download page with path parameter
$app->get('/download/{param}', function ($request, $response, $args) {
    $param = $args['param'];
    // You might want to do something with $param
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/download.html'));
    return $response;
});

// Run the application
$app->run();
