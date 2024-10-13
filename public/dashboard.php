<?php
session_start();
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/controllers/AuthController.php';
require_once __DIR__ . '/src/controllers/FileController.php';

$auth = new AuthController($pdo);
$fileController = new FileController($pdo);

if (!$auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$user = $auth->getCurrentUser();
$files = $fileController->getUserFiles($user['userID']);

require_once __DIR__ . '/src/views/dashboard_view.php';