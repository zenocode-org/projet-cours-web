<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db.php';

function jsonResponse($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getJsonInput(): array {
    $input = file_get_contents('php://input');
    return $input ? json_decode($input, true) ?? [] : [];
}
