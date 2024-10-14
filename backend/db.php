<?php
function getDbConnection() {
    // $host = getenv('DB_HOST');
    // $dbname = getenv('DB_NAME');
    // $user = getenv('DB_USER');
    // $pass = getenv('DB_PASS');
    $host="db";
    $dbname = "mydatabase";
    $user = "user";
    $pass = "password";

    $dsn = "pgsql:host=$host;dbname=$dbname";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}