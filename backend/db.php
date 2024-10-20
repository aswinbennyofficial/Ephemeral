<?php
use Aws\S3\S3Client;


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


function getR2Client() {
    return new S3Client([
        'version' => 'latest',
        'region'  => 'auto',
        'endpoint' => 'https://4035172a05dbebfde7c744cd1173c8cc.r2.cloudflarestorage.com',
        'credentials' => [
            'key'    => '4713d93e4afa150e84ed0f38c1dedd46',
            'secret' => '9be9ffe6eb1f67b58c3bd6468aa2300a390ae917921b17460804b90cf1b373aa'
        ],
    ]);
}