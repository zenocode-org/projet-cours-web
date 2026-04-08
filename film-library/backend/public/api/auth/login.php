<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password required'], 400);
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'Invalid credentials'], 401);
}

$_SESSION['user_id'] = (int) $user['id'];
jsonResponse(['message' => 'Logged in', 'user_id' => (int) $user['id']]);
