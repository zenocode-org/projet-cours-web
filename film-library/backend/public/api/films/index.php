<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $statusFilter = $_GET['status'] ?? null;
    $favoritesOnly = isset($_GET['favorites']) && $_GET['favorites'] === '1';

    $sql = 'SELECT f.id, f.omdb_id, f.title, f.year, f.poster_url, f.plot, f.genre, f.status, f.rating, f.created_at,
            EXISTS(SELECT 1 FROM film_favorites ff WHERE ff.user_id = ? AND ff.film_id = f.id) AS is_favorite
            FROM films f WHERE f.user_id = ?';
    $params = [$userId, $userId];

    if ($statusFilter && in_array($statusFilter, ['to_watch', 'seen'])) {
        $sql .= ' AND f.status = ?';
        $params[] = $statusFilter;
    }

    if ($favoritesOnly) {
        $sql .= ' AND EXISTS(SELECT 1 FROM film_favorites ff WHERE ff.user_id = ? AND ff.film_id = f.id)';
        $params[] = $userId;
    }

    $sql .= ' ORDER BY f.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $films = $stmt->fetchAll();

    foreach ($films as &$f) {
        $f['id'] = (int) $f['id'];
        $f['rating'] = $f['rating'] !== null ? (int) $f['rating'] : null;
        $f['is_favorite'] = (bool) $f['is_favorite'];
    }

    jsonResponse(['films' => $films]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();

    $title = trim($input['title'] ?? '');
    if (!$title) {
        jsonResponse(['error' => 'Title is required'], 400);
    }

    $omdbId = trim($input['omdb_id'] ?? '') ?: null;
    $year = trim($input['year'] ?? '') ?: null;
    $posterUrl = trim($input['poster_url'] ?? '') ?: null;
    $plot = trim($input['plot'] ?? '') ?: null;
    $genre = trim($input['genre'] ?? '') ?: null;
    $status = $input['status'] ?? 'to_watch';
    $rating = isset($input['rating']) ? (int) $input['rating'] : null;

    if (!in_array($status, ['to_watch', 'seen'])) {
        $status = 'to_watch';
    }
    if ($rating !== null && ($rating < 0 || $rating > 10)) {
        $rating = null;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO films (user_id, omdb_id, title, year, poster_url, plot, genre, status, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?::film_status, ?)');
        $stmt->execute([$userId, $omdbId, $title, $year, $posterUrl, $plot, $genre, $status, $rating]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'unique') !== false) {
            jsonResponse(['error' => 'Film already in collection'], 409);
        }
        throw $e;
    }

    $filmId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT id, omdb_id, title, year, poster_url, plot, genre, status, rating, created_at FROM films WHERE id = ?');
    $stmt->execute([$filmId]);
    $film = $stmt->fetch();
    $film['is_favorite'] = false;
    $film['id'] = (int) $film['id'];
    $film['rating'] = $film['rating'] !== null ? (int) $film['rating'] : null;

    jsonResponse($film, 201);
}

jsonResponse(['error' => 'Method not allowed'], 405);
