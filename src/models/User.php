<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($email, $firstName, $lastName, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (email, firstName, lastName, passwordHash) VALUES (?, ?, ?, ?) RETURNING userID";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email, $firstName, $lastName, $passwordHash]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Log the error
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception("Could not register user.");
        }
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyPassword($userID, $password) {
        $query = "SELECT passwordHash FROM users WHERE userID = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userID]);
        $hash = $stmt->fetchColumn();
        return password_verify($password, $hash);
    }
}