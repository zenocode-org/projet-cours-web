<?php
require_once __DIR__ . '/api-init.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = getJsonInput();
    $displayName = trim($input['display_name'] ?? '');
    $newPassword = $input['password'] ?? '';

    $updates = [];
    $params = [];

    if ($displayName !== '') {
        $updates[] = 'display_name = ?';
        $params[] = $displayName;
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
        }
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'Nothing to update'], 400);
    }

    $params[] = $userId;
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($params);

    jsonResponse(['message' => 'Profile updated']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
