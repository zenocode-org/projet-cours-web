<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('
        SELECT f.id, f.omdb_id, f.title, f.year, f.poster_url, f.status, f.rating, f.created_at
        FROM films f
        JOIN film_favorites ff ON ff.film_id = f.id AND ff.user_id = ?
        WHERE f.user_id = ?
        ORDER BY ff.created_at DESC
    ');
    $stmt->execute([$userId, $userId]);
    $films = $stmt->fetchAll();
    foreach ($films as &$f) {
        $f['id'] = (int) $f['id'];
        $f['rating'] = $f['rating'] !== null ? (int) $f['rating'] : null;
        $f['is_favorite'] = true;
    }
    jsonResponse(['films' => $films]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $filmId = (int) ($input['film_id'] ?? 0);
    if (!$filmId) {
        jsonResponse(['error' => 'film_id required'], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM films WHERE id = ? AND user_id = ?');
    $stmt->execute([$filmId, $userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Film not found'], 404);
    }

    try {
        $pdo->prepare('INSERT INTO film_favorites (user_id, film_id) VALUES (?, ?)')->execute([$userId, $filmId]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'unique') !== false) {
            jsonResponse(['message' => 'Already favorited']);
            return;
        }
        throw $e;
    }
    jsonResponse(['message' => 'Added to favorites'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = getJsonInput();
    $filmId = (int) ($input['film_id'] ?? $_GET['film_id'] ?? 0);
    if (!$filmId) {
        jsonResponse(['error' => 'film_id required'], 400);
    }

    $pdo->prepare('DELETE FROM film_favorites WHERE user_id = ? AND film_id = ?')->execute([$userId, $filmId]);
    jsonResponse(['message' => 'Removed from favorites']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
