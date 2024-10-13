<?php
session_start();
require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/controllers/FileController.php';

$fileController = new FileController($pdo);

$slug = $_GET['slug'] ?? '';
$password = $_POST['password'] ?? '';
$download = isset($_GET['download']) && $_GET['download'] === 'true';

try {
    $file = $fileController->getFileBySlug($slug);
    
    if ($download) {
        if ($file['passwordHash'] && !$fileController->verifyFilePassword($file['fileID'], $password)) {
            throw new Exception('Invalid password');
        }
        
        $filePath = $fileController->getFilePath($file['fileID']);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['metadata']['originalName'] . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once __DIR__ . '/src/views/download_view.php';