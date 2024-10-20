<?php
require 'vendor/autoload.php';
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Create the application instance
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Middleware to handle CORS and JSON response types
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        // ->withHeader('Content-Type', 'application/json');
});

// Custom JWT Authentication middleware
// $app->add(function (Request $request, RequestHandler $handler) {
//     $path = $request->getUri()->getPath();
//     $publicPaths = ['/api/login', '/api/register'];
    
//     if (strpos($path, '/api') === 0 && !in_array($path, $publicPaths)) {
//         $token = $request->getHeaderLine('Authorization');
        
//         if (!$token) {
//             $response = new Response();
//             return $response
//                 ->withStatus(401)
//                 ->withHeader('Content-Type', 'application/json')
//                 ->getBody()->write(json_encode(['error' => 'Invalid token']));
//         }

//         try {
//             $token = str_replace('Bearer ', '', $token);
//             $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
//             $request = $request->withAttribute('user', $decoded);
//         } catch (Exception $e) {
//             $response = new Response();
//             return $response
//                 ->withStatus(401)
//                 ->withHeader('Content-Type', 'application/json')
//                 ->getBody()->write(json_encode(['error' => 'Invalid token']));
//         }
//     }

//     return $handler->handle($request);
// });

// Load routes from routes.php
require 'routes.php';

// Run the application
$app->run();