<?php

// Handler for the landing page
function landingPageHandler($request, $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/landing.html'));
    return $response;
}

// Handler for the dashboard page (no params)
function dashboardPageHandler($request, $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/public/dashboard.html'));
    return $response;
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
