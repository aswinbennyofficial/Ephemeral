<?php
session_start();
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

$auth = new AuthController($pdo);

phpinfo();

if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

require_once __DIR__ . '/src/views/landing.php';