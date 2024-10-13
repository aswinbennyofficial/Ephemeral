<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $user;

    public function __construct($pdo) {
        $this->user = new User($pdo);
    }

    public function register($email, $firstName, $lastName, $password) {
        if ($this->user->findByEmail($email)) {
            throw new Exception("Email already exists");
        }

        // Debugging: Log the values being inserted
        error_log("Trying to Creating user with email: $email, firstName: $firstName, lastName: $lastName");


        $userID = $this->user->create($email, $firstName, $lastName, $password);
        $_SESSION['user_id'] = $userID;
        return $userID;
    }

    public function login($email, $password) {
        $user = $this->user->findByEmail($email);
        if (!$user || !$this->user->verifyPassword($user['userID'], $password)) {
            throw new Exception("Invalid email or password");
        }
        $_SESSION['user_id'] = $user['userID'];
        return $user['userID'];
    }

    public function logout() {
        unset($_SESSION['user_id']);
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        // Fetch user details from the database
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE userID = :userID");
        $stmt->execute(['userID' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}