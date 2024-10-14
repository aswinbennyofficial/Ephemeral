<?php
require_once 'db.php';
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;



// Handler for the landing page
function landingPageHandler($request, $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/landing.html'));
    return $response->withHeader('Content-Type', 'text/html');
}

// Handler for the dashboard page (no params)
function dashboardPageHandler($request, $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/dashboard.html'));
    return $response->withHeader('Content-Type', 'text/html');
}

// Handler for the download page
// In handlers.php

function downloadPageHandler($request, $response, $args) {
    // Get the fileID from the route parameters
    $fileID = $args['fileID'];

    // Load the download.html file contents
    $htmlContent = file_get_contents(__DIR__ . '/public/download.html');

    // Create a JavaScript variable to hold the fileID
    $jsVariable = "<script>const fileID = " . json_encode($fileID) . ";</script>";

    // Prepend the JS variable to the HTML content
    $htmlContent = $jsVariable . $htmlContent;

    // Write the modified content to the response
    $response->getBody()->write($htmlContent);

    // Return the response with the content type set to HTML
    return $response->withHeader('Content-Type', 'text/html');
}

function loginHandler($request, $response) {
    $db = getDbConnection();
    // $data = $request->getParsedBody();
    $data = json_decode($request->getBody(), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        return jsonResponse($response, ['error' => 'email and password are required'], 400);
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // JWT valid for 1 hour
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user['id'],
            'email' => $email
        ];

        $token = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
        return jsonResponse($response, ['token' => $token]);
    } else {
        return jsonResponse($response, ['error' => 'Invalid credentials'], 401);
    }
}

// Fix register handler
function registerHandler($request, $response) {
    $db = getDbConnection();
    // Decode the JSON body
    $data = json_decode($request->getBody(), true);

    // Extract the required fields
    $firstName = $data['firstName'] ?? '';
    $lastName = $data['lastName'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validate input
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        return jsonResponse($response, ['error' => 'First name, last name, email, and password are required'], 400);
    }

    // Check if email already exists
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return jsonResponse($response, ['error' => 'Email already exists'], 409);
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert the user into the database
    $stmt = $db->prepare('INSERT INTO users (email, first_name, last_name, passwordhash) VALUES (:email, :first_name, :last_name, :passwordhash)');
    if ($stmt->execute([
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'passwordhash' => $hashedPassword
    ])) {
        // Get the user ID of the newly registered user
        // $userId = $db->lastInsertId();

        // Prepare the JWT token payload
        $payload = [
            'email' => $email,
            // 'userId' => $userId,
            'iat' => time(), // Issued at
            'exp' => time() + 60 * 60 // Expiration time (1 hour)
        ];

        // Generate the JWT token
        $jwt = JWT::encode($payload, 'your_secret_key', 'HS256');

        // Set the JWT as an HTTP-only cookie
        setcookie('jwt', $jwt, [
            'expires' => time() + 3600, // 1 hour
            'path' => '/', // Available across the entire domain
            'domain' => '', // Leave empty for the current domain
            'secure' => false, // Set to true in production (HTTPS)
            'httponly' => true, // Not accessible via JavaScript
            'samesite' => 'Lax' // SameSite attribute for CSRF protection
        ]);

        // Redirect to the dashboard (or respond with a success message)
        return jsonResponse($response, [
            'message' => 'User registered successfully',
            'redirect' => '/dashboard' // Send redirect URL
        ]);
    } else {
        return jsonResponse($response, ['error' => 'Failed to register user'], 500);
    }
}


// Utility function to return JSON response
function jsonResponse($response, $data, $status = 200) {
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus($status);
}

// Handler for file upload
function uploadFileHandler($request, $response) {
    $uploadedFiles = $request->getUploadedFiles();
    $filePassword = $request->getParsedBody()['password'] ?? null;

    if (empty($uploadedFiles['file'])) {
        return $response->withStatus(400)->withJson(['error' => 'No file uploaded']);
    }

    $file = $uploadedFiles['file'];
    $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
    $fileID = Uuid::uuid4()->toString();
    $fileName = $fileID . '.' . $extension;

    $file->moveTo(__DIR__ . '/uploads/' . $fileName);

    // TODO: Save file metadata to database, including password hash and expiry date
    $expiryDate = date('Y-m-d', strtotime('+7 days'));

    return $response->withJson([
        'fileID' => $fileID,
        'expiryDate' => $expiryDate
    ]);
}

// Handler to get user's files
function getFilesHandler($request, $response) {
    // TODO: Retrieve files for the authenticated user from the database
    // For now, we'll return dummy data
    $files = [
        ['id' => 'file1', 'name' => 'example1.txt', 'expiryDate' => '2023-05-01'],
        ['id' => 'file2', 'name' => 'example2.pdf', 'expiryDate' => '2023-05-03']
    ];

    return $response->withJson($files);
}

// Handler to delete a file
function deleteFileHandler($request, $response, $args) {
    $fileID = $args['fileID'];

    // TODO: Implement file deletion logic
    // 1. Check if the file belongs to the authenticated user
    // 2. Delete the file from storage
    // 3. Remove the file entry from the database

    // For now, we'll just return a success message
    return $response->withJson(['message' => 'File deleted successfully']);
}