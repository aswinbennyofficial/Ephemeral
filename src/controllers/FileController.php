<?php
require_once __DIR__ . '/../models/File.php';

class FileController {
    private $file;

    public function __construct($pdo) {
        $this->file = new File($pdo);
    }

    public function getUserFiles($userID) {
        // Fetch files associated with the user from the database
        $stmt = $this->pdo->prepare("SELECT * FROM files WHERE userID = :userID");
        $stmt->execute(['userID' => $userID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function upload($userID, $fileData, $password = null) {
        $uploadDir = __DIR__ . '/../../public/uploads/' . $userID . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $fileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            throw new Exception("Failed to upload file");
        }

        $url = '/uploads/' . $userID . '/' . $fileName;
        $slug = $this->generateSlug();
        $expiry = date('Y-m-d H:i:s', strtotime('+7 days'));
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        $metadata = [
            'originalName' => $fileData['name'],
            'size' => $fileData['size'],
            'type' => $fileData['type']
        ];

        return $this->file->create($userID, $url, $slug, $expiry, $passwordHash, $metadata);
    }

    public function download($slug, $password = null) {
        $fileInfo = $this->file->findBySlug($slug);
        if (!$fileInfo) {
            throw new Exception("File not found");
        }

        if ($fileInfo['passwordHash'] && !password_verify($password, $fileInfo['passwordHash'])) {
            throw new Exception("Invalid password");
        }

        $filePath = __DIR__ . '/../../public' . $fileInfo['url'];
        if (!file_exists($filePath)) {
            throw new Exception("File not found on server");
        }

        return $filePath;
    }

    private function generateSlug($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $slug;
    }



}