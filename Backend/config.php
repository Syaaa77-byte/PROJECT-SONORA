<?php
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'tubes_webpro_rating';
const DB_USER = 'root';
const DB_PASS = '';

// Isi dengan OAuth 2.0 Client ID dari Google Cloud Console untuk mengaktifkan Login via Google.
// Contoh: '1234567890-abcdefg.apps.googleusercontent.com'
// Authorized JavaScript origins: http://localhost:8000 dan http://localhost
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '1045845599987-lmb3v844iuafi2utcul9ttpkbbs8ge8h.apps.googleusercontent.com');
