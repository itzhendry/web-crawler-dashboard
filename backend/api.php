<?php
// backend/api.php

header('Content-Type: application/json');

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'config.php';
require 'crawler.php';

/**
 * Send a JSON response with a given HTTP status code.
 *
 * @param int $code HTTP status code.
 * @param array $data The data to send as JSON.
 */
function sendResponse($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(200, []);
}

// Check for API key in Authorization header
$headers = getallheaders();
error_log(print_r($headers, true)); // Log headers for debugging

$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (strpos($authHeader, 'Bearer ') !== 0) {
    sendResponse(401, ['error' => 'Unauthorized']);
}

$providedKey = substr($authHeader, 7); // Remove 'Bearer ' prefix

if ($providedKey !== API_KEY) {
    sendResponse(401, ['error' => 'Unauthorized']);
}

// Determine the request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle adding a new URL
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url']) || empty($input['url'])) {
        sendResponse(400, ['error' => 'URL is required']);
    }

    $newUrl = filter_var($input['url'], FILTER_VALIDATE_URL);
    if (!$newUrl) {
        sendResponse(400, ['error' => 'Invalid URL']);
    }

    // Append the new URL to urls.txt
    if (file_put_contents('urls.txt', $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
        sendResponse(500, ['error' => 'Failed to add URL']);
    }

    sendResponse(200, ['success' => 'URL added successfully']);
} elseif ($method === 'GET') {
    // Handle crawling existing URLs
    if (!file_exists('urls.txt')) {
        sendResponse(500, ['error' => 'URLs file not found']);
    }

    $urls = file('urls.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$urls) {
        sendResponse(500, ['error' => 'No URLs found']);
    }

    $results = [];
    foreach ($urls as $url) {
        $data = crawlWebsite($url);
        if ($data) {
            $results[] = $data;
        }
    }

    sendResponse(200, $results);
} else {
    // Method not allowed
    sendResponse(405, ['error' => 'Method not allowed']);
}
?>
