<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$query = trim($_GET['q'] ?? $_GET['s'] ?? '');
if (strlen($query) < 2) {
    jsonResponse(['error' => 'Search query must be at least 2 characters'], 400);
}

$apiKey = $_ENV['OMDB_API_KEY'] ?? '';
if (!$apiKey) {
    jsonResponse(['error' => 'OMDB API key not configured'], 503);
}

$url = 'https://www.omdbapi.com/?apikey=' . urlencode($apiKey) . '&s=' . urlencode($query) . '&type=movie';

$response = @file_get_contents($url);
if ($response === false) {
    jsonResponse(['error' => 'Failed to fetch from OMDB'], 502);
}

$data = json_decode($response, true);
if (!$data || isset($data['Error'])) {
    jsonResponse(['Search' => [], 'totalResults' => '0']);
}

jsonResponse($data);
