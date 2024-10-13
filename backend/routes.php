<?php
// Import the Slim application instance
use Slim\App;

// Import the handler functions
require 'handlers.php';

// Define routes
$app->get('/', 'landingPageHandler'); // Landing page route
$app->get('/dashboard', 'dashboardPageHandler'); // Dashboard page route without params
$app->get('/download/{fileID}', 'downloadPageHandler');
