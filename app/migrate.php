<?php
// migrate.php
require 'db.php';

// Create tables
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    userID UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$createFilesTable = "
CREATE TABLE IF NOT EXISTS files (
    uuid UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    url VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry TIMESTAMP NOT NULL,
    passwordHash VARCHAR(255),
    userID UUID REFERENCES users(userID)
);
";

try {
    // Execute the create table statements
    $pdo->exec($createUsersTable);
    $pdo->exec($createFilesTable);
    echo "Tables created successfully.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
