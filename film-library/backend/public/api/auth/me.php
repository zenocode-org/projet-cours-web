<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$userId = getCurrentUserId();
if (!$userId) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$stmt = $pdo->prepare('SELECT id, email, display_name, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'User not found'], 404);
}

jsonResponse([
    'id' => (int) $user['id'],
    'email' => $user['email'],
    'display_name' => $user['display_name'] ?? '',
    'created_at' => $user['created_at'],
]);
