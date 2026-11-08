<?php require_once __DIR__ . '/../Backend/config.php'; ?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk - Sonora</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/style.css"></head>
<body class="auth-page">
  <main class="auth-card">
    <a class="brand" href="index.php"><span>SON</span>ORA</a>
    <p class="eyebrow">SELAMAT DATANG KEMBALI</p><h1>Masuk ke Sonora</h1>
    <form id="loginForm" class="auth-form">
      <label>Email<input type="email" name="email" required></label>
      <label>Password<input type="password" name="password" required></label>
      <p class="form-error" id="formError"></p>
      <button class="btn btn-dark btn-wide" type="submit">Masuk</button>
    </form>
    <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </main>
  <script src="assets/auth.js"></script>
</body>
</html>
