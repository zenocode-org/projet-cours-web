<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Corps JSON invalide'], JSON_UNESCAPED_UNICODE);
    exit;
}

$libelle = '';
if (array_key_exists('libelle', $data)) {
    $libelle = trim((string) $data['libelle']);
}

if (strlen($libelle) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'libelle dépasse 255 caractères'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = api_get_pdo();
    $stmt = $pdo->prepare('INSERT INTO exemple (libelle) VALUES (:libelle)');
    $stmt->execute(['libelle' => $libelle]);
    $id = (int)$pdo->lastInsertId();

    http_response_code(201);
    echo json_encode(
        ['id' => $id, 'libelle' => $libelle],
        JSON_UNESCAPED_UNICODE
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données'], JSON_UNESCAPED_UNICODE);
}
