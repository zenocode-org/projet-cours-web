<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function requireAuth(): int {
    $userId = getCurrentUserId();
    if (!$userId) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return (int) $userId;
}
