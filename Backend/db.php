<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    exit(json_encode([
        'error' => 'Database belum siap. Import Backend/database.sql melalui phpMyAdmin.'
    ], JSON_UNESCAPED_UNICODE));
}
