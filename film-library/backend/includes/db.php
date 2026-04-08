<?php
require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'] ?? 'postgres';
$dbname = $_ENV['DB_NAME'] ?? 'film_library';
$user = $_ENV['DB_USER'] ?? 'film_user';
$password = $_ENV['DB_PASSWORD'] ?? 'film_pass';

$dsn = "pgsql:host=$host;dbname=$dbname;options=--client_encoding=utf8";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
