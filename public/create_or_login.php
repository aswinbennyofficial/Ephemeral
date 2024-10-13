<?php
session_start();
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

$auth = new AuthController($pdo);

if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$action = $_GET['action'] ?? 'login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        if ($action === 'register') {
            $firstName = $_POST['firstName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';
            $auth->register($email, $firstName, $lastName, $password);
        } else {
            $auth->login($email, $password);
        }
        header('Location: /dashboard.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/src/views/create_login_form.php';