<?php require_once __DIR__ . '/../Backend/config.php'; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sonora - Music Rating</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <nav class="nav">
    <a class="brand" href="index.php"><span>SON</span>ORA</a>
    <div class="nav-search"><span>⌕</span><input id="search" type="search" placeholder="Cari lagu atau artis..." aria-label="Cari lagu"></div>
    <div class="nav-actions">
      <?php if (isset($_SESSION['user'])): ?>
        <span class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
        <?php if ($_SESSION['user']['role'] === 'admin'): ?><button class="btn btn-dark" onclick="openSongModal()">+ Tambah Lagu</button><?php endif; ?>
        <button class="btn btn-light" onclick="logout()">Keluar</button>
      <?php else: ?>
        <a class="btn btn-light" href="login.php">Masuk</a>
        <a class="btn btn-dark" href="register.php">Daftar</a>
      <?php endif; ?>
    </div>
  </nav>

  <main>
    <section class="hero">
      <div>
        <p class="eyebrow">TEMUKAN SUARA FAVORITMU</p>
        <h1>Setiap lagu punya<br><em>cerita.</em></h1>
        <p>Jelajahi koleksi, bagikan pendapatmu, dan temukan lagu berikutnya yang akan kamu putar berulang kali.</p>
      </div>
      <div class="hero-mark">♫</div>
    </section>

    <section>
      <div class="section-heading">
        <div><p class="eyebrow">KOLEKSI</p><h2>Lagu pilihan</h2></div>
        <span id="songCount"></span>
      </div>
      <div class="song-grid" id="songGrid"><div class="loading">Memuat lagu...</div></div>
    </section>

    <section>
      <div class="section-heading">
        <div><p class="eyebrow">KATALOG</p><h2>Jelajahi musik</h2></div>
        <span>Katalog lokal</span>
      </div>
      <div class="song-grid catalog-grid" id="catalogGrid"><div class="loading">Memuat katalog...</div></div>
    </section>
  </main>

  <dialog id="songDialog">
    <form id="songForm">
      <div class="dialog-head">
        <h2 id="songFormTitle">Tambah lagu</h2>
        <button type="button" class="icon-btn" onclick="closeSongModal()">×</button>
      </div>

      <input type="hidden" name="id">
      <div class="form-grid song-title-field">
        <label>Judul lagu<input name="title" autocomplete="off" required>
        <div id="songSuggestions" class="song-suggestions"></div>
        </label><label>Artis<input name="artist" required></label>
      </div>

      <div class="form-grid">
        <label>Album<input name="album"></label>
        <label>Genre<input name="genre"></label>
      </div>

      <div class="form-grid">
        <label>Tahun<input name="year" type="number" min="1900" max="2100"></label>
        <label>URL sampul<input name="cover_url" type="url"></label>
      </div>

      <label>Deskripsi<textarea name="description" rows="4"></textarea></label>
      <div class="dialog-actions">
        <button type="button" class="btn btn-light" onclick="closeSongModal()">Batal</button>
        <button class="btn btn-dark" type="submit">Simpan lagu</button>
      </div>
    </form>
  </dialog>

  <div id="toast"></div>
  <script>window.APP_USER = <?= json_encode($_SESSION['user'] ?? null) ?>;</script>
  <script src="assets/app.js"></script>
</body>
</html>
