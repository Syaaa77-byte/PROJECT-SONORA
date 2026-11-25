<?php require_once __DIR__ . '/../Backend/config.php'; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sonora - Music Rating</title>
  <script>(function(){try{if(localStorage.getItem('sonora_theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <nav class="nav">
    <a class="brand" href="index.php"><span>SON</span>ORA</a>
    <div class="nav-search">
      <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="8"></circle>
        <path d="m21 21-4.35-4.35"></path>
      </svg>
      <input id="search" type="search" placeholder="Cari lagu, artis, atau genre..." aria-label="Cari lagu">
    </div>
    <div class="nav-actions">
      <button type="button" id="themeToggle" class="theme-toggle" aria-label="Ganti ke Mode Gelap"></button>
      <?php if (isset($_SESSION['user'])):
        $u = $_SESSION['user'];
        $displayName = trim((string) ($u['name'] ?? 'Pengguna'));
        $initial = strtoupper(mb_substr($displayName, 0, 1));
        $isAdmin = (($u['role'] ?? 'user') === 'admin');
      ?>
        <div class="user-badge" title="<?= htmlspecialchars($displayName) ?>">
          <?php if (!empty($u['avatar'])): ?>
            <img class="user-avatar-img" src="<?= htmlspecialchars($u['avatar']) ?>" alt="<?= htmlspecialchars($displayName) ?>" referrerpolicy="no-referrer">
          <?php else: ?>
            <span class="user-avatar-fallback"><?= htmlspecialchars($initial) ?></span>
          <?php endif; ?>
          <span class="user-name"><?= htmlspecialchars($displayName) ?></span>
          <?php if ($isAdmin): ?>
            <span class="role-badge role-badge-admin">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3Z"/></svg>
              Admin
            </span>
          <?php endif; ?>
        </div>
        <?php if ($isAdmin): ?><button class="btn btn-dark" onclick="openSongModal()">+ Tambah Lagu</button><?php endif; ?>
        <button class="btn btn-light" onclick="logout()">Keluar</button>
      <?php else: ?>
        <a class="btn btn-light" href="login.php">Masuk</a>
        <a class="btn btn-dark" href="register.php">Daftar</a>
      <?php endif; ?>
    </div>
  </nav>

  <main>
    <section class="hero">
      <div class="hero-content">
        <p class="eyebrow">TEMUKAN SUARA FAVORITMU</p>
        <h1>Setiap lagu punya<br><em>cerita.</em></h1>
        <p class="hero-copy">Jelajahi koleksi, beri rating bintang, bagikan ulasanmu, dan temukan lagu yang paling sering diputar ulang.</p>
        <div class="hero-footer">
          <a class="btn btn-hero" href="#koleksi">Mulai Beri Nilai</a>
          <div class="hero-stats" id="heroStats">Memuat koleksi...</div>
        </div>
      </div>
      <div class="hero-mark" aria-hidden="true">♫</div>
    </section>

    <section id="topRatedSection" class="top-section">
      <div class="section-heading">
        <div><p class="eyebrow">PERINGKAT TERATAS</p><h2>Top Rated</h2></div>
        <span>3 lagu terpopuler</span>
      </div>
      <div class="top-grid" id="topRatedGrid"><div class="loading">Memuat peringkat...</div></div>
    </section>

    <section id="koleksi">
      <div class="section-heading">
        <div><p class="eyebrow">KOLEKSI</p><h2>Lagu pilihan</h2></div>
        <span id="songCount">Memuat...</span>
      </div>

      <div class="filter-bar">
        <div class="genre-pills" id="genreFilters" role="tablist" aria-label="Filter genre">
          <button type="button" class="genre-pill active" data-genre="">Semua</button>
        </div>
        <label class="sort-wrap">
          <span>Urutkan:</span>
          <select id="sortSelect" aria-label="Urutkan lagu">
            <option value="rating_desc">Rating Tertinggi</option>
            <option value="newest">Terbaru</option>
            <option value="most_rated">Paling Banyak Dinilai</option>
            <option value="unrated">Belum Dinilai</option>
          </select>
        </label>
      </div>

      <div class="song-grid" id="songGrid"><div class="loading">Memuat lagu...</div></div>
    </section>
  </main>

  <dialog id="detailDialog" class="detail-dialog">
    <div class="detail-modal-inner">
      <button type="button" class="icon-btn detail-close" onclick="closeDetailModal()" aria-label="Tutup detail">×</button>
      <div id="detailContent"><div class="loading">Memuat detail lagu...</div></div>
    </div>
  </dialog>

  <dialog id="songDialog">
    <form id="songForm">
      <div class="dialog-head">
        <h2 id="songFormTitle">Tambah lagu</h2>
        <button type="button" class="icon-btn" onclick="closeSongModal()" aria-label="Tutup form">×</button>
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

      <label>Deskripsi<textarea name="description" rows="3"></textarea></label>
      <div class="dialog-actions">
        <button type="button" class="btn btn-light" onclick="closeSongModal()">Batal</button>
        <button class="btn btn-dark" type="submit">Simpan lagu</button>
      </div>
    </form>
  </dialog>

  <div id="toast" role="status" aria-live="polite"></div>
  <script>window.APP_USER = <?= json_encode($_SESSION['user'] ?? null) ?>;</script>
  <script src="assets/theme.js"></script>
  <script src="assets/app.js"></script>
</body>
</html>
