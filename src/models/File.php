<?php
class File {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($userID, $url, $slug, $expiry, $passwordHash = null, $metadata = null) {
        $query = "INSERT INTO files (userID, url, slug, expiry, passwordHash, metadata) VALUES (?, ?, ?, ?, ?, ?) RETURNING fileID";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userID, $url, $slug, $expiry, $passwordHash, json_encode($metadata)]);
        return $stmt->fetchColumn();
    }

    public function findBySlug($slug) {
        $query = "SELECT * FROM files WHERE slug = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteExpired() {
        $query = "DELETE FROM files WHERE expiry <= CURRENT_TIMESTAMP";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }
}