<?php
// api.php

header('Content-Type: application/json');

require 'config.php';
require 'crawler.php';

// Kontrolli API võtme olemasolu
$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer ' . API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Kontrolli HTTP meetodit
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Luge URL-id
$urls = file('urls.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$urls) {
    http_response_code(500);
    echo json_encode(['error' => 'No URLs found']);
    exit;
}

$results = [];
foreach ($urls as $url) {
    $data = crawlWebsite($url);
    if ($data) {
        $results[] = $data;
    }
}

echo json_encode($results);
?>
