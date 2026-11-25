<?php
require_once __DIR__ . '/db.php';

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_input(string $value): string
{
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function require_login(): array
{
    if (!isset($_SESSION['user'])) {
        json_response(['error' => 'Silakan login terlebih dahulu.'], 401);
    }
    return $_SESSION['user'];
}

function require_admin(): array
{
    $user = require_login();
    if (($user['role'] ?? 'user') !== 'admin') {
        json_response(['error' => 'Akses khusus administrator.'], 403);
    }
    return $user;
}

function song_list(
    mysqli $db,
    string $search = '',
    string $genre = '',
    string $sort = 'rating_desc',
    int $limit = 100
): array {
    $sql = "SELECT s.*,
            COALESCE(ROUND(AVG(r.score), 1), 0) AS average_rating,
            COUNT(r.id) AS rating_count
            FROM songs s LEFT JOIN ratings r ON r.song_id = s.id";

    $where = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $where[] = "(s.title LIKE ? OR s.artist LIKE ? OR s.album LIKE ? OR s.genre LIKE ?)";
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
        $types .= 'ssss';
    }

    if ($genre !== '' && strtolower($genre) !== 'semua') {
        $where[] = "s.genre = ?";
        $params[] = $genre;
        $types .= 's';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' GROUP BY s.id';

    if ($sort === 'unrated') {
        $sql .= ' HAVING COUNT(r.id) = 0';
    }

    $orderMap = [
        'newest' => 'ORDER BY s.created_at DESC, s.id DESC',
        'most_rated' => 'ORDER BY rating_count DESC, average_rating DESC, s.created_at DESC',
        'unrated' => 'ORDER BY s.created_at DESC, s.id DESC',
        'rating_desc' => 'ORDER BY average_rating DESC, rating_count DESC, s.created_at DESC',
    ];
    $sql .= ' ' . ($orderMap[$sort] ?? $orderMap['rating_desc']) . ' LIMIT ?';

    $params[] = $limit;
    $types .= 'i';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function song_stats(mysqli $db): array
{
    $row = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM songs) AS total_songs,
            (SELECT COUNT(*) FROM ratings) AS total_reviews"
    )->fetch_assoc();

    return [
        'total_songs' => (int) ($row['total_songs'] ?? 0),
        'total_reviews' => (int) ($row['total_reviews'] ?? 0),
    ];
}

function song_genres(mysqli $db): array
{
    $res = $db->query(
        "SELECT DISTINCT genre FROM songs
         WHERE genre IS NOT NULL AND genre <> ''
         ORDER BY genre ASC"
    );
    $genres = [];
    while ($row = $res->fetch_assoc()) {
        $genres[] = $row['genre'];
    }
    return $genres;
}

function top_rated_songs(mysqli $db, int $limit = 3): array
{
    $stmt = $db->prepare(
        "SELECT s.*,
                COALESCE(ROUND(AVG(r.score), 1), 0) AS average_rating,
                COUNT(r.id) AS rating_count
         FROM songs s
         LEFT JOIN ratings r ON r.song_id = s.id
         GROUP BY s.id
         ORDER BY average_rating DESC, rating_count DESC, s.id ASC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function song_detail(mysqli $db, int $id, ?int $currentUserId = null): ?array
{
    $stmt = $db->prepare(
        "SELECT s.*,
                COALESCE(ROUND(AVG(r.score), 1), 0) AS average_rating,
                COUNT(r.id) AS rating_count
         FROM songs s
         LEFT JOIN ratings r ON r.song_id = s.id
         WHERE s.id = ?
         GROUP BY s.id"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $song = $stmt->get_result()->fetch_assoc();
    if (!$song) {
        return null;
    }

    $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $bStmt = $db->prepare(
        "SELECT score, COUNT(*) AS total
         FROM ratings
         WHERE song_id = ?
         GROUP BY score"
    );
    $bStmt->bind_param('i', $id);
    $bStmt->execute();
    $bRes = $bStmt->get_result();
    while ($row = $bRes->fetch_assoc()) {
        $scoreKey = (int) $row['score'];
        if (isset($breakdown[$scoreKey])) {
            $breakdown[$scoreKey] = (int) $row['total'];
        }
    }

    $rStmt = $db->prepare(
        "SELECT r.id, r.score, r.review, r.created_at,
                u.id AS user_id, u.name AS user_name, u.role AS user_role, u.avatar AS user_avatar
         FROM ratings r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.song_id = ?
         ORDER BY r.created_at DESC, r.id DESC"
    );
    $rStmt->bind_param('i', $id);
    $rStmt->execute();
    $reviews = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $userRating = null;
    if ($currentUserId !== null) {
        $uStmt = $db->prepare(
            "SELECT score, review FROM ratings WHERE song_id = ? AND user_id = ? LIMIT 1"
        );
        $uStmt->bind_param('ii', $id, $currentUserId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        if ($uRow) {
            $userRating = [
                'score' => (int) $uRow['score'],
                'review' => (string) ($uRow['review'] ?? ''),
            ];
        }
    }

    return [
        'song' => $song,
        'breakdown' => $breakdown,
        'reviews' => $reviews,
        'user_rating' => $userRating,
    ];
}
