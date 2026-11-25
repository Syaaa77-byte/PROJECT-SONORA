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

if ($method !== 'POST') {
    json_response(['error' => 'Method tidak diizinkan.'], 405);
}

if (GOOGLE_CLIENT_ID === '') {
    json_response(['error' => 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID di Backend/config.php.'], 503);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$credential = trim((string) ($input['credential'] ?? ''));
if ($credential === '') {
    json_response(['error' => 'Token Google tidak valid.'], 422);
}

$context = stream_context_create([
    'http' => ['timeout' => 10, 'ignore_errors' => true],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
]);
$raw = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential), false, $context);
$payload = $raw !== false ? json_decode($raw, true) : null;

$validIssuer = is_array($payload) && in_array($payload['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
$emailVerified = is_array($payload) && (($payload['email_verified'] ?? false) === true || ($payload['email_verified'] ?? '') === 'true');
$email = is_array($payload) ? strtolower(trim((string) ($payload['email'] ?? ''))) : '';
$googleId = is_array($payload) ? trim((string) ($payload['sub'] ?? '')) : '';

if (
    !is_array($payload) ||
    !$validIssuer ||
    ($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID ||
    !$emailVerified ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $googleId === ''
) {
    json_response(['error' => 'Verifikasi akun Google gagal. Silakan coba lagi.'], 401);
}

$name = clean_input((string) ($payload['name'] ?? explode('@', $email)[0]));
if ($name === '') {
    $name = explode('@', $email)[0];
}
$avatar = filter_var((string) ($payload['picture'] ?? ''), FILTER_VALIDATE_URL) ?: null;

$lookup = $db->prepare('SELECT id, name, email, role, google_id, avatar FROM users WHERE google_id = ? OR email = ? LIMIT 1');
$lookup->bind_param('ss', $googleId, $email);
$lookup->execute();
$existing = $lookup->get_result()->fetch_assoc();

if ($existing) {
    $mergedGoogleId = !empty($existing['google_id']) ? $existing['google_id'] : $googleId;
    $mergedAvatar = $avatar ?? ($existing['avatar'] ?? null);
    $upd = $db->prepare('UPDATE users SET google_id = ?, avatar = ? WHERE id = ?');
    $upd->bind_param('ssi', $mergedGoogleId, $mergedAvatar, $existing['id']);
    $upd->execute();

    $_SESSION['user'] = [
        'id' => (int) $existing['id'],
        'name' => $existing['name'],
        'email' => $existing['email'],
        'role' => $existing['role'],
        'avatar' => $mergedAvatar,
    ];
} else {
    $role = 'user';
    $ins = $db->prepare('INSERT INTO users (name, email, password, role, google_id, avatar) VALUES (?, ?, NULL, ?, ?, ?)');
    $ins->bind_param('sssss', $name, $email, $role, $googleId, $avatar);
    $ins->execute();

    $_SESSION['user'] = [
        'id' => (int) $ins->insert_id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'avatar' => $avatar,
    ];
}

json_response(['message' => 'Login dengan Google berhasil.', 'user' => $_SESSION['user']]);
