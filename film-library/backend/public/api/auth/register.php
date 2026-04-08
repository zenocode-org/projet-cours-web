<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$displayName = trim($input['display_name'] ?? '');

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password required'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email already registered'], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)');
$stmt->execute([$email, $passwordHash, $displayName]);

$userId = (int) $pdo->lastInsertId();
$_SESSION['user_id'] = $userId;

jsonResponse(['message' => 'Registered', 'user_id' => $userId], 201);
