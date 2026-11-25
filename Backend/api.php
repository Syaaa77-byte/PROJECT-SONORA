<?php
require_once __DIR__ . '/functions.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost', 'http://localhost:8000', 'http://127.0.0.1:8000'];
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: http://localhost:8000');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
} else {
    $input = $_GET;
}

if ($action === 'register' && $method === 'POST') {
    $name = clean_input((string) ($input['name'] ?? ''));
    $email = strtolower(clean_input((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        json_response(['error' => 'Nama, email valid, dan password minimal 6 karakter diperlukan.'], 422);
    }
    $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
    $exists->bind_param('s', $email);
    $exists->execute();
    if ($exists->get_result()->fetch_assoc()) {
        json_response(['error' => 'Email sudah terdaftar.'], 409);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hash);
    $stmt->execute();
    $_SESSION['user'] = [
        'id' => $stmt->insert_id,
        'name' => $name,
        'email' => $email,
        'role' => 'user',
        'avatar' => null,
    ];
    json_response(['message' => 'Pendaftaran berhasil.', 'user' => $_SESSION['user']]);
}

if ($action === 'login' && $method === 'POST') {
    $email = strtolower(clean_input((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $stmt = $db->prepare('SELECT id, name, email, password, role, avatar FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        json_response(['error' => 'Email atau password salah.'], 401);
    }
    if (empty($user['password'])) {
        json_response(['error' => 'Akun ini terdaftar melalui Google. Silakan masuk dengan tombol Lanjutkan dengan Google.'], 401);
    }
    if (!password_verify($password, $user['password'])) {
        json_response(['error' => 'Email atau password salah.'], 401);
    }
    unset($user['password']);
    $_SESSION['user'] = $user;
    json_response(['message' => 'Login berhasil.', 'user' => $user]);
}

if ($action === 'logout' && $method === 'POST') {
    session_destroy();
    json_response(['message' => 'Logout berhasil.']);
}

if ($action === 'songs' && $method === 'GET') {
    $search = clean_input((string) ($input['search'] ?? ''));
    $genre = trim((string) ($input['genre'] ?? ''));
    $sort = trim((string) ($input['sort'] ?? 'rating_desc'));
    json_response([
        'songs' => song_list($db, $search, $genre, $sort),
        'stats' => song_stats($db),
        'genres' => song_genres($db),
        'top_rated' => top_rated_songs($db, 3),
    ]);
}

if ($action === 'song' && $method === 'GET') {
    $id = (int) ($input['id'] ?? 0);
    $currentUserId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    $detail = song_detail($db, $id, $currentUserId);
    if (!$detail) {
        json_response(['error' => 'Lagu tidak ditemukan.'], 404);
    }
    json_response($detail);
}

if (in_array($action, ['add-song', 'update-song', 'delete-song'], true)) {
    require_admin();
}

if ($action === 'add-song' && $method === 'POST') {
    $title = clean_input((string) ($input['title'] ?? ''));
    $artist = clean_input((string) ($input['artist'] ?? ''));
    if ($title === '' || $artist === '') {
        json_response(['error' => 'Judul dan artis wajib diisi.'], 422);
    }
    $stmt = $db->prepare('INSERT INTO songs (title, artist, album, genre, year, cover_url, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $album = clean_input((string) ($input['album'] ?? ''));
    $genre = clean_input((string) ($input['genre'] ?? ''));
    $year = ($input['year'] ?? '') !== '' ? (int) $input['year'] : null;
    $cover = clean_input((string) ($input['cover_url'] ?? ''));
    $description = clean_input((string) ($input['description'] ?? ''));
    $stmt->bind_param('ssssiss', $title, $artist, $album, $genre, $year, $cover, $description);
    $stmt->execute();
    json_response(['message' => 'Lagu berhasil ditambahkan.', 'id' => $stmt->insert_id], 201);
}

if ($action === 'update-song' && $method === 'POST') {
    $id = (int) ($input['id'] ?? 0);
    $title = clean_input((string) ($input['title'] ?? ''));
    $artist = clean_input((string) ($input['artist'] ?? ''));
    if ($id < 1 || $title === '' || $artist === '') {
        json_response(['error' => 'Data lagu tidak valid.'], 422);
    }
    $stmt = $db->prepare('UPDATE songs SET title=?, artist=?, album=?, genre=?, year=?, cover_url=?, description=? WHERE id=?');
    $album = clean_input((string) ($input['album'] ?? ''));
    $genre = clean_input((string) ($input['genre'] ?? ''));
    $year = ($input['year'] ?? '') !== '' ? (int) $input['year'] : null;
    $cover = clean_input((string) ($input['cover_url'] ?? ''));
    $description = clean_input((string) ($input['description'] ?? ''));
    $stmt->bind_param('ssssissi', $title, $artist, $album, $genre, $year, $cover, $description, $id);
    $stmt->execute();
    json_response(['message' => 'Lagu berhasil diperbarui.']);
}

if ($action === 'delete-song' && $method === 'POST') {
    $id = (int) ($input['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM songs WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['message' => 'Lagu berhasil dihapus.']);
}

if ($action === 'rate' && $method === 'POST') {
    $user = require_login();
    $songId = (int) ($input['song_id'] ?? 0);
    $score = (int) ($input['score'] ?? 0);
    $review = clean_input((string) ($input['review'] ?? ''));
    if ($songId < 1 || $score < 1 || $score > 5) {
        json_response(['error' => 'Rating harus bernilai 1 sampai 5.'], 422);
    }
    $songCheck = $db->prepare('SELECT id FROM songs WHERE id = ? LIMIT 1');
    $songCheck->bind_param('i', $songId);
    $songCheck->execute();
    if (!$songCheck->get_result()->fetch_assoc()) {
        json_response(['error' => 'Lagu tidak ditemukan.'], 404);
    }
    $stmt = $db->prepare('INSERT INTO ratings (song_id, user_id, score, review) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE score=VALUES(score), review=VALUES(review)');
    $stmt->bind_param('iiis', $songId, $user['id'], $score, $review);
    $stmt->execute();
    json_response(['message' => 'Rating berhasil disimpan.']);
}

json_response(['error' => 'Endpoint tidak ditemukan.'], 404);
