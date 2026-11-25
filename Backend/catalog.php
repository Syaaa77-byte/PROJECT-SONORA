<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function search_itunes(string $query, int $limit = 10): array
{
    $url = 'https://itunes.apple.com/search?' . http_build_query([
        'term' => $query,
        'entity' => 'song',
        'limit' => $limit,
        'country' => 'id',
    ]);
    $context = stream_context_create([
        'http' => ['timeout' => 12, 'header' => "User-Agent: Sonora/1.0\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data['results'] ?? null) ? $data['results'] : [];
}

function track_data(array $track): array
{
    $cover = $track['artworkUrl100'] ?? '';
    if ($cover !== '') {
        $cover = str_replace('100x100bb', '600x600bb', $cover);
    }
    return [
        'title' => $track['trackName'] ?? '',
        'artist' => $track['artistName'] ?? '',
        'album' => $track['collectionName'] ?? '',
        'genre' => $track['primaryGenreName'] ?? '',
        'year' => isset($track['releaseDate']) ? (int) substr($track['releaseDate'], 0, 4) : null,
        'cover_url' => $cover,
        'description' => (($track['artistName'] ?? '') !== '' ? ($track['artistName'] ?? '') . ' - ' : '') . ($track['trackName'] ?? ''),
    ];
}

if (isset($_GET['q'])) {
    $query = trim((string) $_GET['q']);
    if (mb_strlen($query) < 2) {
        echo json_encode(['tracks' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $tracks = [];
    foreach (array_slice(search_itunes($query, 8), 0, 8) as $track) {
        $item = track_data($track);
        if ($item['title'] !== '' && $item['artist'] !== '') {
            $tracks[] = $item;
        }
    }
    echo json_encode(['tracks' => $tracks], JSON_UNESCAPED_UNICODE);
    exit;
}

$totalRow = $db->query('SELECT COUNT(*) AS total FROM songs')->fetch_assoc();
if (((int) ($totalRow['total'] ?? 0)) > 0 && !isset($_GET['force'])) {
    echo json_encode(['added' => 0, 'skipped' => true, 'message' => 'Katalog sudah tersedia'], JSON_UNESCAPED_UNICODE);
    exit;
}

$added = 0;
foreach (search_itunes('top hits all', 30) as $track) {
    $item = track_data($track);
    if ($item['title'] === '' || $item['artist'] === '') continue;

    $check = $db->prepare('SELECT id FROM songs WHERE title = ? AND artist = ? LIMIT 1');
    $check->bind_param('ss', $item['title'], $item['artist']);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) continue;

    $stmt = $db->prepare('INSERT INTO songs (title, artist, album, genre, year, cover_url, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssiss', $item['title'], $item['artist'], $item['album'], $item['genre'], $item['year'], $item['cover_url'], $item['description']);
    if ($stmt->execute()) $added++;
}

echo json_encode(['added' => $added, 'message' => 'Katalog diperbarui'], JSON_UNESCAPED_UNICODE);
