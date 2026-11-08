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

function song_list(mysqli $db, string $search = '', int $limit = 100): array
{
    $sql = "SELECT s.*,
            COALESCE(ROUND(AVG(r.score), 1), 0) AS average_rating,
            COUNT(r.id) AS rating_count
            FROM songs s LEFT JOIN ratings r ON r.song_id = s.id";
    $params = [];
    $types = '';
    if ($search !== '') {
        $sql .= " WHERE s.title LIKE ? OR s.artist LIKE ? OR s.album LIKE ? OR s.genre LIKE ?";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
        $types = 'ssss';
    }
    $sql .= ' GROUP BY s.id ORDER BY s.created_at DESC LIMIT ?';
    $params[] = $limit;
    $types .= 'i';
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
