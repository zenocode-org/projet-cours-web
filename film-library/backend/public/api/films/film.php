<?php
require_once dirname(__DIR__) . '/api-init.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

$userId = requireAuth();

$filmId = (int) ($_GET['id'] ?? 0);
if (!$filmId) {
    jsonResponse(['error' => 'Film ID required'], 400);
}

$stmt = $pdo->prepare('SELECT id, user_id, omdb_id, title, year, poster_url, plot, genre, status, rating, created_at FROM films WHERE id = ? AND user_id = ?');
$stmt->execute([$filmId, $userId]);
$film = $stmt->fetch();

if (!$film) {
    jsonResponse(['error' => 'Film not found'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT 1 FROM film_favorites WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    $film['is_favorite'] = (bool) $stmt->fetch();

    $stmt = $pdo->prepare('SELECT c.id, c.content, c.created_at, u.display_name FROM film_comments c JOIN users u ON u.id = c.user_id WHERE c.film_id = ? ORDER BY c.created_at ASC');
    $stmt->execute([$filmId]);
    $film['comments'] = $stmt->fetchAll();
    foreach ($film['comments'] as &$c) {
        $c['id'] = (int) $c['id'];
    }

    $film['id'] = (int) $film['id'];
    $film['user_id'] = (int) $film['user_id'];
    $film['rating'] = $film['rating'] !== null ? (int) $film['rating'] : null;

    jsonResponse($film);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = getJsonInput();

    $updates = [];
    $params = [];

    $allowed = ['title', 'year', 'poster_url', 'plot', 'genre', 'status', 'rating'];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            if ($field === 'rating') {
                $val = $input[$field];
                if ($val === null || $val === '') {
                    $updates[] = 'rating = NULL';
                } elseif (is_numeric($val) && $val >= 0 && $val <= 10) {
                    $updates[] = 'rating = ?';
                    $params[] = (int) $val;
                }
            } elseif ($field === 'status') {
                if (in_array($input[$field], ['to_watch', 'seen'])) {
                    $updates[] = 'status = ?::film_status';
                    $params[] = $input[$field];
                }
            } else {
                $updates[] = $field . ' = ?';
                $params[] = trim((string) $input[$field]) ?: null;
            }
        }
    }

    if (isset($input['comment']) && trim($input['comment']) !== '') {
        $stmt = $pdo->prepare('INSERT INTO film_comments (user_id, film_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $filmId, trim($input['comment'])]);
    }

    if (!empty($updates)) {
        $params[] = $filmId;
        $params[] = $userId;
        $sql = 'UPDATE films SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?';
        $pdo->prepare($sql)->execute($params);
    }

    $stmt = $pdo->prepare('SELECT id, omdb_id, title, year, poster_url, plot, genre, status, rating, created_at FROM films WHERE id = ? AND user_id = ?');
    $stmt->execute([$filmId, $userId]);
    $film = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT 1 FROM film_favorites WHERE user_id = ? AND film_id = ?');
    $stmt->execute([$userId, $filmId]);
    $film['is_favorite'] = (bool) $stmt->fetch();
    $stmt = $pdo->prepare('SELECT c.id, c.content, c.created_at, u.display_name FROM film_comments c JOIN users u ON u.id = c.user_id WHERE c.film_id = ? ORDER BY c.created_at ASC');
    $stmt->execute([$filmId]);
    $film['comments'] = $stmt->fetchAll();
    $film['id'] = (int) $film['id'];
    $film['rating'] = $film['rating'] !== null ? (int) $film['rating'] : null;

    jsonResponse($film);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $pdo->prepare('DELETE FROM films WHERE id = ? AND user_id = ?')->execute([$filmId, $userId]);
    jsonResponse(['message' => 'Film removed']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
