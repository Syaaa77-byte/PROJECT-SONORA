<?php
require_once __DIR__ . '/../Backend/config.php';
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar - Sonora</title>
  <script>(function(){try{if(localStorage.getItem('sonora_theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <?php if (GOOGLE_CLIENT_ID !== ''): ?>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <?php endif; ?>
</head>
<body class="auth-page">
  <button type="button" id="themeToggle" class="theme-toggle auth-theme-toggle" aria-label="Ganti ke Mode Gelap"></button>
  <main class="auth-card">
    <a class="brand" href="index.php"><span>SON</span>ORA</a>
    <p class="eyebrow">BERGABUNG BERSAMA KAMI</p>
    <h1>Buat akun baru</h1>

    <div id="googleAuthBox" class="google-auth-box">
      <div id="googleBtnMount" class="google-btn-mount"></div>
      <button type="button" id="googleFallbackBtn" class="btn-google">
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/>
          <path fill="#34A853" d="M12 24c3.3 0 6.08-1.09 8.11-2.96l-3.88-3.05c-1.09.74-2.49 1.18-4.23 1.18-3.25 0-6-2.19-6.98-5.15H1.01v3.14C3.05 21.2 7.21 24 12 24Z"/>
          <path fill="#FBBC05" d="M5.02 14.02A7.18 7.18 0 0 1 4.64 12c0-.7.13-1.38.38-2.02V6.84H1.01A11.98 11.98 0 0 0 0 12c0 1.94.46 3.78 1.01 5.16l4.01-3.14Z"/>
          <path fill="#EA4335" d="M12 4.77c1.8 0 3.41.62 4.69 1.84l3.51-3.51C18.07 1.19 15.3 0 12 0 7.21 0 3.05 2.8 1.01 6.84l4.01 3.14C6 6.96 8.75 4.77 12 4.77Z"/>
        </svg>
        <span>Lanjutkan dengan Google</span>
      </button>
    </div>

    <div class="auth-divider"><span>atau daftar dengan email</span></div>

    <form id="registerForm" class="auth-form">
      <label>Nama<input name="name" required></label>
      <label>Email<input type="email" name="email" required></label>
      <label>Password<input type="password" name="password" minlength="6" required></label>
      <p class="form-error" id="formError"></p>
      <button class="btn btn-dark btn-wide" type="submit">Daftar</button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk</a></p>
  </main>
  <script>window.GOOGLE_CLIENT_ID = <?= json_encode(GOOGLE_CLIENT_ID) ?>;</script>
  <script src="assets/theme.js"></script>
  <script src="assets/auth.js"></script>
</body>
</html>
