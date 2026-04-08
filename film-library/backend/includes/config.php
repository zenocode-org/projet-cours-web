<?php
// Load .env from project root (film-library/.env) when running locally
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}
// Ensure Docker-injected env vars are available in $_ENV
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'OMDB_API_KEY'] as $key) {
    if (empty($_ENV[$key]) && ($v = getenv($key)) !== false) {
        $_ENV[$key] = $v;
    }
}
