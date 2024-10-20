<?php
// Import the Slim application instance
use Slim\App;
use Slim\Http\Request;
use Slim\Psr7\Response;

// Import the handler functions
require 'handlers.php';

// Define routes
$app->get('/', 'landingPageHandler');
$app->get('/dashboard', 'dashboardPageHandler');
$app->get('/download/{fileID}', 'downloadPageHandler');

// New routes for authentication and file handling
$app->post('/api/login', 'loginHandler');
$app->post('/api/register', 'registerHandler');
$app->post('/api/upload', 'uploadFileHandler');
// $app->get('/api/files', 'getFilesHandler');
$app->get('/api/files/metadata', 'getFilesMetadataHandler');
$app->get('/api/files/{fileID}', 'getFileHandler');
$app->delete('/api/files/{fileID}', 'deleteFileHandler');