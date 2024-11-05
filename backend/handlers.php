<?php
require_once 'db.php';
use Firebase\JWT\JWT;
use Ulid\Ulid;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;





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

// function downloadPageHandler($request, $response, $args) {
//     // Get the fileID from the route parameters
//     $slug = $args['slug'];

//     // Load the download.html file contents
//     $htmlContent = file_get_contents(__DIR__ . '/public/download.html');

//     // Create a JavaScript variable to hold the fileID
//     $jsVariable = "<script>const slug = " . json_encode($slug) . ";</script>";

//     // Prepend the JS variable to the HTML content
//     $htmlContent = $jsVariable . $htmlContent;

//     // Write the modified content to the response
//     $response->getBody()->write($htmlContent);

//     // Return the response with the content type set to HTML
//     return $response->withHeader('Content-Type', 'text/html');
// }

function downloadPageHandler($request, $response, $args) {
    try {
        // Get the slug from the route parameters
        $slug = $args['slug'] ?? null;
        
        // Validate slug exists
        if (!$slug) {
            $body = $response->getBody();
            $body->write('<h1>404 - File Not Found</h1>');
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/html')
                ->withBody($body);
        }

        // Validate slug format (assuming it should be alphanumeric + hyphens)
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $slug)) {
            $body = $response->getBody();
            $body->write('<h1>400 - Invalid File ID</h1>');
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'text/html')
                ->withBody($body);
        }

        // Verify file exists in database
        $db = getDbConnection();
        $stmt = $db->prepare('SELECT fileid FROM files WHERE slug = :slug AND expiry > CURRENT_TIMESTAMP');
        $stmt->execute(['slug' => $slug]);
        
        if (!$stmt->fetch()) {
            $body = $response->getBody();
            $body->write('<h1>404 - File Not Found or Expired</h1>');
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/html')
                ->withBody($body);
        }

        // Load the download page template
        $templatePath = __DIR__ . '/public/download.html';
        if (!file_exists($templatePath)) {
            throw new RuntimeException('Template file not found');
        }

        $htmlContent = file_get_contents($templatePath);
        if ($htmlContent === false) {
            throw new RuntimeException('Failed to read template file');
        }

        // Replace the placeholder with the actual slug
        $htmlContent = str_replace('${slug}', htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'), $htmlContent);

        // Write to response body
        $body = $response->getBody();
        $body->write($htmlContent);

        return $response
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($body);

    } catch (Exception $e) {
        error_log('Download page error: ' . $e->getMessage());
        $body = $response->getBody();
        $body->write('<h1>500 - Internal Server Error</h1>');
        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'text/html')
            ->withBody($body);
    }
}


function loginHandler($request, $response) {
    $db = getDbConnection();
    
    // Parse the JSON body
    $data = json_decode($request->getBody(), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Check for empty email or password
    if (empty($email) || empty($password)) {
        return jsonResponse($response, ['error' => 'Email and password are required'], 400);
    }

    // Query to fetch user by email
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (empty($user)) {
        return jsonResponse($response, ['error' => 'Invalid credentials. No user exists in db'], 401);
    }

    // Check if user exists and the password hash is valid
    if ($user && isset($user['passwordhash']) && password_verify($password, $user['passwordhash'])) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'email' => $email,
        ];

        // Generate JWT token
        $token = JWT::encode($payload, 'your_secret_key', 'HS256');
        
        // Set the JWT token as an HTTP-only cookie
        setcookie('jwt', $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => false, // Set to true in production (HTTPS)
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return jsonResponse($response, ['message' => 'Login successful']);
    } else {
        // Return invalid credentials error
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

    $userId = Ulid::generate(); // Generate a ULID
    // log the user id
    error_log($userId);


    // Insert the user into the database
    $stmt = $db->prepare('INSERT INTO users (userid, email, firstname, lastname, passwordhash) VALUES (:userid, :email, :first_name, :last_name, :passwordhash)');
    if ($stmt->execute([
        'userid' => $userId,
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

        // Generate the JWT token and fetch the jwt secret key from the environment
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

// function uploadFileHandler($request, $response) {
//     try {
//         // Early validation of JWT token
//         if (!isset($_COOKIE['jwt'])) {
//             return jsonResponse($response, ['error' => 'Authentication required. JWT token missing.'], 401);
//         }

//         // Load JWT secret from environment variable or config
//         $jwtSecret = 'your_secret_key';
//         if (!$jwtSecret) {
//             throw new RuntimeException('JWT secret key not configured');
//         }

//         // Validate JWT and extract user email
//         try {
//             $jwtToken = $_COOKIE['jwt'];
//             $decodedJwt = JWT::decode($jwtToken, new Key($jwtSecret, 'HS256'));
//             $userEmail = $decodedJwt->email;
//         } catch (Exception $e) {
//             return jsonResponse($response, [
//                 'error' => 'Invalid authentication token',
//                 'details' => $e->getMessage()
//             ], 401);
//         }

//         // Validate uploaded file
//         $uploadedFiles = $request->getUploadedFiles();
//         if (empty($uploadedFiles['file'])) {
//             return jsonResponse($response, ['error' => 'No file uploaded'], 400);
//         }

//         $file = $uploadedFiles['file'];
//         $parsedBody = $request->getParsedBody();
//         $filePassword = $parsedBody['password'] ?? null;
//         $customSlug = $parsedBody['slug'] ?? null;

//         // Validate file size and type
//         $maxFileSize = 25 * 1024 * 1024; // 25MB limit
//         if ($file->getSize() > $maxFileSize) {
//             return jsonResponse($response, ['error' => 'File size exceeds limit'], 400);
//         }

//         // Get file details
//         $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
//         $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
//         if (!in_array($extension, $allowedExtensions)) {
//             return jsonResponse($response, ['error' => 'File type not allowed'], 400);
//         }

//         $fileId = (string) Ulid::generate(true);
//         $fileName = $fileId . '.' . $extension;

//         // Database operations
//         $db = getDbConnection();
//         $db->beginTransaction();

//         try {
//             // Check slug uniqueness if provided
//             if ($customSlug) {
//                 $stmt = $db->prepare('SELECT slug FROM files WHERE slug = :slug');
//                 $stmt->execute(['slug' => $customSlug]);
//                 if ($stmt->fetch()) {
//                     $db->rollBack();
//                     return jsonResponse($response, ['error' => 'Slug already in use'], 409);
//                 }
//             }

//             $slug = $customSlug ?: (string) Ulid::generate(true);

//             // Upload to R2
//             $r2Client = getR2Client();
//             $r2Client->putObject([
//                 'Bucket' => 'ephermeral',
//                 'Key' => $fileName,
//                 'Body' => fopen($file->getStream()->getMetadata('uri'), 'r'),
//                 'ContentType' => $file->getClientMediaType(),
//                 'ACL' => 'public-read',
//                 'Expires' => '+7 days'
//             ]);

//             // Insert file metadata
//             $expiryDate = date('Y-m-d H:i:s', strtotime('+7 days'));
//             $passwordHash = $filePassword ? password_hash($filePassword, PASSWORD_BCRYPT) : null;

//             // Create metadata JSON
//             $metadata = json_encode([
//                 'originalName' => $file->getClientFilename(),
//                 'mimeType' => $file->getClientMediaType(),
//                 'size' => $file->getSize(),
//                 'uploadedAt' => date('Y-m-d H:i:s')
//             ]);

//             // Updated SQL to match schema
//             $stmt = $db->prepare('
//                 INSERT INTO files (fileid, userid, url, slug, expiry, passwordhash, metadata)
//                 VALUES (:fileid, (SELECT userid FROM users WHERE email = :email),
//                         :url, :slug, :expiry, :passwordhash, :metadata)
//             ');

//             $baseUrl = getenv('R2_PUBLIC_URL') ?: 'https://ephermeral.r2.cloudflarestorage.com';
//             $stmt->execute([
//                 'fileid' => $fileId,
//                 'url' => $baseUrl . '/' . $fileName,
//                 'slug' => $slug,
//                 'expiry' => $expiryDate,
//                 'passwordhash' => $passwordHash,
//                 'email' => $userEmail,
//                 'metadata' => $metadata
//             ]);

//             $db->commit();

//             return jsonResponse($response, [
//                 'message' => 'File uploaded successfully',
//                 'fileid' => $fileId,
//                 'slug' => $slug,
//                 'expiryDate' => $expiryDate
//             ]);

//         } catch (Exception $e) {
//             $db->rollBack();
//             throw $e;
//         }

//     } catch (Exception $e) {
//         error_log('File upload error: ' . $e->getMessage());
//         return jsonResponse($response, [
//             'error' => 'File upload failed',
//             'details' => $e->getMessage()
//         ], 500);
//     }
// }


function uploadFileHandler($request, $response) {
    try {
        // Early validation of JWT token
        if (!isset($_COOKIE['jwt'])) {
            return jsonResponse($response, ['error' => 'Authentication required. JWT token missing.'], 401);
        }

        // Load JWT secret from environment variable or config
        $jwtSecret = 'your_secret_key';
        if (!$jwtSecret) {
            throw new RuntimeException('JWT secret key not configured');
        }

        // Validate JWT and extract user email
        try {
            $jwtToken = $_COOKIE['jwt'];
            $decodedJwt = JWT::decode($jwtToken, new Key($jwtSecret, 'HS256'));
            $userEmail = $decodedJwt->email;
        } catch (Exception $e) {
            return jsonResponse($response, [
                'error' => 'Invalid authentication token',
                'details' => $e->getMessage()
            ], 401);
        }

        // Validate uploaded file
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return jsonResponse($response, ['error' => 'No file uploaded'], 400);
        }

        $file = $uploadedFiles['file'];
        $parsedBody = $request->getParsedBody();
        $filePassword = $parsedBody['password'] ?? null;
        $customSlug = $parsedBody['slug'] ?? null;

        // Validate file size and type
        $maxFileSize = 25 * 1024 * 1024; // 25MB limit
        if ($file->getSize() > $maxFileSize) {
            return jsonResponse($response, ['error' => 'File size exceeds the limit of 25MB'], 400);
        }

        // Get file details
        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
        if (!in_array($extension, $allowedExtensions)) {
            return jsonResponse($response, ['error' => 'File type not allowed'], 400);
        }

        $fileId = (string) Ulid::generate(true);
        $fileName = $fileId . '.' . $extension;
        $objectKey = 'files/' . $fileName;

        // Database operations
        $db = getDbConnection();
        $db->beginTransaction();

        try {
            // Check slug uniqueness if provided
            if ($customSlug) {
                $stmt = $db->prepare('SELECT slug FROM files WHERE slug = :slug');
                $stmt->execute(['slug' => $customSlug]);
                if ($stmt->fetch()) {
                    $db->rollBack();
                    return jsonResponse($response, ['error' => 'Slug already in use'], 409);
                }
            }

            $slug = $customSlug ?: (string) Ulid::generate(true);

            // Upload to R2
            try {
                $r2Client = getR2Client();
                $r2Client->putObject([
                    'Bucket' => 'ephermeral',
                    'Key' => $objectKey,
                    'Body' => fopen($file->getStream()->getMetadata('uri'), 'r'),
                    'ContentType' => $file->getClientMediaType(),
                    'ACL' => 'public-read',
                    'Expires' => '+7 days'
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                return jsonResponse($response, [
                    'error' => 'File upload to R2 failed',
                    'details' => $e->getMessage()
                ], 500);
            }

            // Insert file metadata
            $expiryDate = date('Y-m-d H:i:s', strtotime('+7 days'));
            $passwordHash = $filePassword ? password_hash($filePassword, PASSWORD_BCRYPT) : null;

            // Create metadata JSON
            $metadata = json_encode([
                'originalName' => $file->getClientFilename(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'uploadedAt' => date('Y-m-d H:i:s')
            ]);

            $baseUrl = getenv('R2_PUBLIC_URL') ?: 'https://ephermeral.r2.cloudflarestorage.com';
            $fileUrl = $baseUrl . '/' . $objectKey;

            $stmt = $db->prepare('
                INSERT INTO files 
                    (fileid, userid, slug, expiry, passwordhash, metadata, objectkey, url)
                VALUES 
                    (:fileid, 
                    (SELECT userid FROM users WHERE email = :email),
                    :slug, 
                    :expiry, 
                    :passwordHash, 
                    :metadata, 
                    :objectkey,
                    :url)
            ');

            $stmt->execute([
                'fileid' => $fileId,
                'slug' => $slug,
                'expiry' => $expiryDate,
                'passwordHash' => $passwordHash,
                'email' => $userEmail,
                'metadata' => $metadata,
                'objectkey' => $objectKey,
                'url' => $fileUrl
            ]);

            $db->commit();

            return jsonResponse($response, [
                'message' => 'File uploaded successfully',
                'fileID' => $fileId,
                'slug' => $slug,
                'expiryDate' => $expiryDate
            ]);

        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Database error: ' . $e->getMessage());
            return jsonResponse($response, [
                'error' => 'File upload failed',
                'details' => 'Database error occurred.'
            ], 500);
        }

    } catch (Exception $e) {
        error_log('File upload error: ' . $e->getMessage());
        return jsonResponse($response, [
            'error' => 'File upload failed',
            'details' => $e->getMessage()
        ], 500);
    }
}

function getFilesMetadataHandler($request, $response) {
    $db = getDbConnection();

    // Early validation of JWT token
    if (!isset($_COOKIE['jwt'])) {
        return jsonResponse($response, ['error' => 'Authentication required. JWT token missing.'], 401);
    }

    // Load JWT secret from environment variable or config
    $jwtSecret = 'your_secret_key'; // Change this to your environment variable if needed
    if (!$jwtSecret) {
        throw new RuntimeException('JWT secret key not configured');
    }

    // Validate JWT and extract user email
    try {
        $jwtToken = $_COOKIE['jwt'];
        $decodedJwt = JWT::decode($jwtToken, new Key($jwtSecret, 'HS256'));
        $userEmail = $decodedJwt->email;

        // Get the user ID from the database using the email
        $userIdStmt = $db->prepare('SELECT userid FROM users WHERE email = :email');
        $userIdStmt->execute(['email' => $userEmail]);
        $userId = $userIdStmt->fetchColumn();

        if (!$userId) {
            return jsonResponse($response, ['error' => 'User not found.'], 404);
        }

    } catch (Exception $e) {
        return jsonResponse($response, [
            'error' => 'Invalid authentication token',
            'details' => $e->getMessage()
        ], 401);
    }

    // Fetch files belonging to the logged-in user
    try {
        $stmt = $db->prepare('SELECT fileid, slug, metadata, expiry FROM files WHERE userid = :userid');
        $stmt->execute(['userid' => $userId]); // Use the retrieved user ID
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Extract necessary metadata
        $formattedFiles = array_map(function($file) {
            $metadata = json_decode($file['metadata'], true); // Assuming metadata is stored as JSON
            return [
                'fileID' => $file['fileid'],
                'slug' => $file['slug'],
                'filename' => $metadata['originalName'] ?? 'Unknown', // Extract filename, default to 'Unknown'
                'size' => $metadata['size'] ?? 0, // Extract size, default to 0
                'expiry' => $file['expiry'], // Keep expiry for calculating remaining time
            ];
        }, $files);

        if (empty($formattedFiles)) {
            return jsonResponse($response, ['message' => 'No files found for this user.'], 404);
        }

    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return jsonResponse($response, [
            'error' => 'Database error occurred while fetching files.',
            'details' => $e->getMessage()
        ], 500);
    }

    return jsonResponse($response, $formattedFiles);

    }







function deleteFileHandler($request, $response, $args) {
    $fileID = $args['fileID'];
    $r2Client = getR2Client();

     // Early validation of JWT token
     if (!isset($_COOKIE['jwt'])) {
        return jsonResponse($response, ['error' => 'Authentication required. JWT token missing.'], 401);
    }

    // Load JWT secret from environment variable or config
    $jwtSecret = 'your_secret_key';
    if (!$jwtSecret) {
        throw new RuntimeException('JWT secret key not configured');
    }

    // Validate JWT and extract user email
    try {
        $jwtToken = $_COOKIE['jwt'];
        $decodedJwt = JWT::decode($jwtToken, new Key($jwtSecret, 'HS256'));
        $userEmail = $decodedJwt->email;
    } catch (Exception $e) {
        return jsonResponse($response, [
            'error' => 'Invalid authentication token',
            'details' => $e->getMessage()
        ], 401);
    }


    // Fetch the file from the database
    $db = getDbConnection();
    // $stmt = $db->prepare('SELECT URL FROM files WHERE fileid = :fileID AND userid = :userID');
    // $stmt->execute([
    //     'fileID' => $fileID,
    //     'userID' => $userEmail
    // ]);
    // $file = $stmt->fetch();


    // if (!$file) {
    //     return jsonResponse($response, ['error' => "File with ID {$fileID} and user {$userEmail} not found or permission denied" ], 404);
    // }

    // Extract the file name from the fileURL
    // $fileName = basename($file['fileURL']);

    // Delete from R2
    try {
        $r2Client->deleteObject([
            'Bucket' => 'ephermeral',
            'Key'    => $fileID
        ]);

        // Delete from the database
        $stmt = $db->prepare('DELETE FROM files WHERE fileID = :fileID');
        $stmt->execute(['fileID' => $fileID]);

        return jsonResponse($response, ['message' => 'File deleted successfully']);
    } catch (Exception $e) {
        return jsonResponse($response, ['error' => 'Failed to delete file: ' . $e->getMessage()], 500);
    }
}


// function downloadFileHandler($request, $response, $args) {
//     try {
//         $db = getDbConnection();
        
//         // Get slug from route parameters
//         $slug = $args['slug'] ?? '';
        
//         // Parse the JSON body for password
//         $data = json_decode($request->getBody(), true);
//         $password = $data['password'] ?? '';

//         // Validate input
//         if (empty($slug)) {
//             return jsonResponse($response, ['error' => 'Slug is required'], 400);
//         }

//         // Fetch file metadata
//         $stmt = $db->prepare('
//             SELECT f.*, u.email as owner_email 
//             FROM files f 
//             JOIN users u ON f.userid = u.userid 
//             WHERE f.slug = :slug AND f.expiry > CURRENT_TIMESTAMP
//         ');
//         $stmt->execute(['slug' => $slug]);
//         $file = $stmt->fetch(PDO::FETCH_ASSOC);

//         if (!$file) {
//             return jsonResponse($response, ['error' => 'File not found or expired'], 404);
//         }

//         // Check if password is required
//         if ($file['passwordhash'] && !$password) {
//             return jsonResponse($response, ['error' => 'Password required'], 401);
//         }

//         // Verify password if set
//         if ($file['passwordhash'] && !password_verify($password, $file['passwordhash'])) {
//             return jsonResponse($response, ['error' => 'Invalid password'], 401);
//         }

//         try {
//             // Get R2 client
//             $r2Client = getR2Client();
            
//             // Generate a pre-signed URL that expires in 5 minutes
//             $command = $r2Client->getCommand('GetObject', [
//                 'Bucket' => 'ephermeral',
//                 'Key' => $file['objectkey']
//             ]);

//             $request = $r2Client->createPresignedRequest($command, '+5 minutes');
//             $presignedUrl = (string)$request->getUri();

//             // Log the download attempt
//             error_log(sprintf(
//                 'Download attempt - Slug: %s, User Email: %s, IP: %s',
//                 $slug,
//                 $file['owner_email'],
//                 $_SERVER['REMOTE_ADDR']
//             ));

//             // Return the pre-signed URL
//             return jsonResponse($response, [
//                 'url' => $presignedUrl,
//                 'filename' => json_decode($file['metadata'], true)['originalName'] ?? $slug
//             ]);

//         } catch (Exception $e) {
//             error_log('R2 error: ' . $e->getMessage());
//             return jsonResponse($response, ['error' => 'Failed to generate download URL'], 500);
//         }

//     } catch (Exception $e) {
//         error_log('Download error: ' . $e->getMessage());
//         return jsonResponse($response, ['error' => 'Internal server error'], 500);
//     }
// }

function downloadFileHandler($request, $response, $args) {
    try {
        $db = getDbConnection();
        
        // Get slug from route parameters
        $slug = $args['slug'] ?? '';
        
        // Parse the JSON body for the password
        $data = json_decode($request->getBody(), true);
        $password = $data['password'] ?? '';

        // Validate input
        if (empty($slug)) {
            return jsonResponse($response, ['error' => 'Slug is required'], 400);
        }

        // Fetch the object key from the files table using the slug
        $stmt = $db->prepare('
            SELECT objectkey, passwordhash, metadata 
            FROM files 
            WHERE slug = :slug AND expiry > CURRENT_TIMESTAMP
        ');
        $stmt->execute(['slug' => $slug]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            return jsonResponse($response, ['error' => 'File not found or expired'], 404);
        }

        // Check if password is required
        if ($file['passwordhash'] && !$password) {
            return jsonResponse($response, ['error' => 'Password required'], 401);
        }

        // Verify password if set
        if ($file['passwordhash'] && !password_verify($password, $file['passwordhash'])) {
            return jsonResponse($response, ['error' => 'Invalid password'], 401);
        }

        try {
            // Get R2 client
            $r2Client = getR2Client();
            
            // Generate a pre-signed URL that expires in 5 minutes
            $command = $r2Client->getCommand('GetObject', [
                'Bucket' => 'ephermeral', // Ensure this matches your bucket name
                'Key' => $file['objectkey'] // Use the fetched object key
            ]);

            // Create the presigned request for downloading the file
            $request = $r2Client->createPresignedRequest($command, '+5 minutes');
            $presignedUrl = (string)$request->getUri();

            // Log the download attempt
            error_log(sprintf(
                'Download attempt - Slug: %s, IP: %s',
                $slug,
                $_SERVER['REMOTE_ADDR']
            ));

            // Return the pre-signed URL along with original filename from metadata
            $metadata = json_decode($file['metadata'], true);
            return jsonResponse($response, [
                'url' => $presignedUrl,
                'filename' => $metadata['originalName'] ?? $slug
            ]);

        } catch (Exception $e) {
            error_log('R2 error: ' . $e->getMessage());
            return jsonResponse($response, ['error' => 'Failed to generate download URL'], 500);
        }

    } catch (Exception $e) {
        error_log('Download error: ' . $e->getMessage());
        return jsonResponse($response, ['error' => 'Internal server error'], 500);
    }
}



// Utility function to return JSON response
function jsonResponse($response, $data, $status = 200) {
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus($status);
}
