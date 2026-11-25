const API = '../Backend/api.php';
const grid = document.querySelector('#songGrid');
const topGrid = document.querySelector('#topRatedGrid');
const genreFilters = document.querySelector('#genreFilters');
const sortSelect = document.querySelector('#sortSelect');
const search = document.querySelector('#search');
const dialog = document.querySelector('#songDialog');
const detailDialog = document.querySelector('#detailDialog');
const detailContent = document.querySelector('#detailContent');
const form = document.querySelector('#songForm');

let currentGenre = '';
let currentSort = 'rating_desc';
let currentSearch = '';
const songsById = new Map();
let activeDetailSongId = null;
let selectedScore = 0;

async function api(action, data = null) {
  const options = { credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } };
  if (data) {
    options.method = 'POST';
    options.body = JSON.stringify(data);
  }
  const response = await fetch(`${API}?action=${action}`, options);
  const contentType = response.headers.get('content-type') || '';
  const result = contentType.includes('application/json')
    ? await response.json()
    : { error: 'Server tidak mengembalikan respons JSON. Periksa database dan konfigurasi PHP.' };
  if (!response.ok) throw new Error(result.error || 'Terjadi kesalahan');
  return result;
}

function toast(message) {
  const box = document.querySelector('#toast');
  box.textContent = message;
  box.classList.add('show');
  setTimeout(() => box.classList.remove('show'), 2600);
}

function escapeHtml(value) {
  const node = document.createElement('div');
  node.textContent = value ?? '';
  return node.innerHTML;
}

function coverMarkup(song, extraClass = '') {
  const inner = song.cover_url
    ? `<img src="${escapeHtml(song.cover_url)}" alt="${escapeHtml(song.title)}" loading="lazy">`
    : escapeHtml((song.title || '?').charAt(0).toUpperCase());
  return `<div class="cover ${extraClass}">${inner}</div>`;
}

function renderStats(stats) {
  const el = document.querySelector('#heroStats');
  if (!el || !stats) return;
  el.textContent = `${stats.total_songs} Lagu · ${stats.total_reviews} Ulasan`;
}

function renderTopRated(topSongs) {
  if (!topGrid) return;
  if (!topSongs.length) {
    topGrid.innerHTML = '<div class="loading">Belum ada lagu di peringkat.</div>';
    return;
  }

  topGrid.innerHTML = topSongs.map((song, idx) => {
    songsById.set(Number(song.id), song);
    const count = Number(song.rating_count || 0);
    const avg = Number(song.average_rating || 0).toFixed(1);
    const thumb = song.cover_url
      ? `<img src="${escapeHtml(song.cover_url)}" alt="${escapeHtml(song.title)}" loading="lazy">`
      : escapeHtml((song.title || '?').charAt(0).toUpperCase());
    return `
      <article class="top-card" data-song-id="${song.id}" tabindex="0">
        <span class="rank-pill rank-${idx + 1}">#${idx + 1}</span>
        <div class="top-thumb">${thumb}</div>
        <div class="top-info">
          <h3 title="${escapeHtml(song.title)}">${escapeHtml(song.title)}</h3>
          <p>${escapeHtml(song.artist)}</p>
          <div class="top-score">
            <span class="star">★</span>
            <span>${count > 0 ? avg : 'Baru'}</span>
            <small>(${count} ulasan)</small>
          </div>
        </div>
      </article>`;
  }).join('');
}

function renderGenreFilters(genres) {
  if (!genreFilters) return;
  const items = [''].concat(genres);
  genreFilters.innerHTML = items.map(genre => {
    const label = genre || 'Semua';
    const active = currentGenre === genre ? 'active' : '';
    return `<button type="button" class="genre-pill ${active}" data-genre="${escapeHtml(genre)}">${escapeHtml(label)}</button>`;
  }).join('');
}

async function loadSongs() {
  try {
    const params = new URLSearchParams();
    if (currentSearch) params.set('search', currentSearch);
    if (currentGenre) params.set('genre', currentGenre);
    if (currentSort) params.set('sort', currentSort);

    const queryStr = params.toString();
    const result = await api(queryStr ? `songs&${queryStr}` : 'songs');

    renderStats(result.stats);
    renderTopRated(result.top_rated || []);
    renderGenreFilters(result.genres || []);

    document.querySelector('#songCount').textContent = `${result.songs.length} lagu`;
    if (!result.songs.length) {
      grid.innerHTML = '<div class="loading">Tidak ada lagu yang cocok dengan filter ini.</div>';
      return;
    }

    const isAdmin = window.APP_USER?.role === 'admin';
    grid.innerHTML = result.songs.map(song => {
      songsById.set(Number(song.id), song);
      const count = Number(song.rating_count || 0);
      const avg = Number(song.average_rating || 0).toFixed(1);
      const badge = count > 0
        ? `<span class="rating-badge"><span class="badge-star">★</span> ${avg}</span>`
        : `<span class="rating-badge unrated">Baru</span>`;
      const coverBody = song.cover_url
        ? `<img src="${escapeHtml(song.cover_url)}" alt="${escapeHtml(song.title)}" loading="lazy">`
        : escapeHtml((song.title || '?').charAt(0).toUpperCase());

      return `
        <article class="song-card" data-song-id="${song.id}" tabindex="0">
          <div class="cover">
            ${coverBody}
            ${badge}
            <div class="cover-overlay"><span class="cover-overlay-pill">★ Beri Rating &amp; Ulasan</span></div>
          </div>
          <div class="song-info">
            <span class="song-meta">${escapeHtml(song.genre || 'Musik')} · ${song.year || '—'}</span>
            <h3 title="${escapeHtml(song.title)}">${escapeHtml(song.title)}</h3>
            <p class="song-artist">${escapeHtml(song.artist)}</p>
            <div class="card-footer-meta">
              <span><strong>${count > 0 ? `★ ${avg}` : 'Belum dinilai'}</strong> (${count})</span>
              <span class="card-cta-hint">Ulas →</span>
            </div>
            ${isAdmin ? `
              <div class="card-actions">
                <button type="button" class="text-btn js-edit-song" data-id="${song.id}">Edit</button>
                <button type="button" class="text-btn danger js-delete-song" data-id="${song.id}">Hapus</button>
              </div>` : ''}
          </div>
        </article>`;
    }).join('');
  } catch (error) {
    grid.innerHTML = `<div class="loading">${escapeHtml(error.message)}</div>`;
  }
}

async function syncCatalog() {
  const response = await fetch('../Backend/catalog.php', { credentials: 'same-origin' });
  const contentType = response.headers.get('content-type') || '';
  if (!response.ok || !contentType.includes('application/json')) {
    throw new Error('Katalog belum dapat disinkronkan.');
  }
}

async function openSongDetail(songId) {
  activeDetailSongId = songId;
  detailContent.innerHTML = '<div class="loading">Memuat detail lagu...</div>';
  if (!detailDialog.open) detailDialog.showModal();

  try {
    const data = await api(`song&id=${encodeURIComponent(songId)}`);
    const song = data.song;
    const breakdown = data.breakdown || {};
    const reviews = data.reviews || [];
    const total = Number(song.rating_count || 0);
    const avg = Number(song.average_rating || 0);
    selectedScore = data.user_rating?.score || 0;

    const breakdownHtml = [5, 4, 3, 2, 1].map(star => {
      const count = Number(breakdown[star] || breakdown[String(star)] || 0);
      const pct = total > 0 ? Math.round((count / total) * 100) : 0;
      return `
        <div class="breakdown-row">
          <span>${star} ★</span>
          <div class="breakdown-track"><div class="breakdown-fill" style="width:${pct}%"></div></div>
          <span class="breakdown-count">${count}</span>
        </div>`;
    }).join('');

    const starsText = '★'.repeat(Math.round(avg)) + '☆'.repeat(5 - Math.round(avg));

    const rateBoxHtml = window.APP_USER
      ? `
        <form id="detailRateForm" class="detail-rate-box">
          <h3>${data.user_rating ? 'Perbarui rating & ulasanmu' : 'Beri rating & ulasan'}</h3>
          <div class="star-picker" id="detailStarPicker">
            ${[1, 2, 3, 4, 5].map(n => `
              <button type="button" class="star-pick-btn ${n <= selectedScore ? 'active' : ''}" data-score="${n}" aria-label="${n} bintang">★</button>
            `).join('')}
            <span class="star-picker-hint" id="starPickerHint">${selectedScore ? `${selectedScore} / 5 bintang` : 'Pilih jumlah bintang'}</span>
          </div>
          <textarea id="detailReviewInput" rows="3" placeholder="Bagikan pendapatmu tentang lagu ini (opsional)...">${escapeHtml(data.user_rating?.review || '')}</textarea>
          <div class="detail-rate-actions">
            <button type="submit" class="btn btn-dark">${data.user_rating ? 'Perbarui Rating' : 'Kirim Rating'}</button>
          </div>
        </form>`
      : `
        <div class="login-prompt-box">
          Ingin memberi bintang dan menulis ulasan? <a href="login.php">Masuk ke akunmu</a> atau lanjutkan dengan Google hanya dalam sekali klik.
        </div>`;

    const reviewsHtml = reviews.length
      ? reviews.map(rev => {
        const name = rev.user_name || 'Pengguna';
        const avatar = rev.user_avatar
          ? `<img class="user-avatar-img" src="${escapeHtml(rev.user_avatar)}" alt="${escapeHtml(name)}" referrerpolicy="no-referrer">`
          : `<span class="user-avatar-fallback">${escapeHtml(name.charAt(0).toUpperCase())}</span>`;
        const adminTag = rev.user_role === 'admin' ? '<span class="role-badge role-badge-admin">Admin</span>' : '';
        const text = rev.review
          ? `<p class="review-text">${escapeHtml(rev.review)}</p>`
          : '<p class="review-text muted-italic">Memberikan rating tanpa ulasan tertulis.</p>';
        return `
          <article class="review-item">
            <div class="review-item-head">
              <div class="review-user">${avatar}<strong>${escapeHtml(name)}</strong>${adminTag}</div>
              <span class="review-score-pill">★ ${Number(rev.score)} / 5</span>
            </div>
            ${text}
            <div class="review-date">${escapeHtml(rev.created_at || '')}</div>
          </article>`;
      }).join('')
      : '<p class="empty-reviews">Belum ada ulasan untuk lagu ini. Jadilah yang pertama memberi nilai!</p>';

    detailContent.innerHTML = `
      <div class="detail-header">
        ${coverMarkup(song, 'detail-cover')}
        <div>
          <span class="detail-meta-pill">${escapeHtml(song.genre || 'Musik')} · ${song.year || '—'}</span>
          <h2>${escapeHtml(song.title)}</h2>
          <p class="detail-artist">${escapeHtml(song.artist)}</p>
          ${song.album ? `<p class="detail-album">Album: ${escapeHtml(song.album)}</p>` : ''}
          ${song.description ? `<p class="detail-description">${escapeHtml(song.description)}</p>` : ''}
        </div>
      </div>

      <div class="detail-summary">
        <div class="summary-score">
          <div class="summary-number">${total > 0 ? avg.toFixed(1) : '0.0'}</div>
          <div class="summary-stars" aria-hidden="true">${starsText}</div>
          <div class="summary-count">${total} ulasan</div>
        </div>
        <div class="breakdown-list">${breakdownHtml}</div>
      </div>

      ${rateBoxHtml}

      <div class="detail-reviews-section">
        <h3>Ulasan Komunitas (${reviews.length})</h3>
        <div class="reviews-list">${reviewsHtml}</div>
      </div>
    `;

    bindDetailInteractions();
  } catch (error) {
    detailContent.innerHTML = `<div class="loading">${escapeHtml(error.message)}</div>`;
  }
}

function bindDetailInteractions() {
  const picker = document.querySelector('#detailStarPicker');
  const rateForm = document.querySelector('#detailRateForm');
  const hint = document.querySelector('#starPickerHint');
  if (!picker || !rateForm) return;

  const stars = picker.querySelectorAll('.star-pick-btn');
  const syncStars = (hoverVal = 0) => {
    const activeVal = hoverVal || selectedScore;
    stars.forEach(btn => {
      const val = Number(btn.dataset.score);
      btn.classList.toggle('active', val <= selectedScore);
      btn.classList.toggle('hover', hoverVal > 0 && val <= hoverVal);
    });
    if (hint) {
      hint.textContent = activeVal ? `${activeVal} / 5 bintang` : 'Pilih jumlah bintang';
    }
  };

  stars.forEach(btn => {
    const score = Number(btn.dataset.score);
    btn.addEventListener('mouseenter', () => syncStars(score));
    btn.addEventListener('mouseleave', () => syncStars(0));
    btn.addEventListener('click', () => {
      selectedScore = score;
      syncStars(0);
    });
  });

  rateForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (selectedScore < 1 || selectedScore > 5) {
      toast('Pilih jumlah bintang (1 sampai 5) terlebih dahulu.');
      return;
    }
    const review = document.querySelector('#detailReviewInput')?.value.trim() || '';
    try {
      await api('rate', { song_id: activeDetailSongId, score: selectedScore, review });
      toast(`Rating ${selectedScore}/5 tersimpan`);
      await Promise.all([openSongDetail(activeDetailSongId), loadSongs()]);
    } catch (error) {
      toast(error.message);
    }
  });
}

function closeDetailModal() {
  detailDialog.close();
}

let suggestionTimer;
form.elements.title.addEventListener('input', () => {
  clearTimeout(suggestionTimer);
  const query = form.elements.title.value.trim();
  const box = document.querySelector('#songSuggestions');
  if (query.length < 2 || form.elements.id.value) {
    box.innerHTML = '';
    box.classList.remove('show');
    return;
  }
  suggestionTimer = setTimeout(() => searchSongSuggestions(query), 350);
});

async function searchSongSuggestions(query) {
  const box = document.querySelector('#songSuggestions');
  box.innerHTML = '<div class="suggestion-status">Mencari lagu...</div>';
  box.classList.add('show');
  try {
    const response = await fetch(`../Backend/catalog.php?q=${encodeURIComponent(query)}`);
    const result = await response.json();
    if (!result.tracks?.length) {
      box.innerHTML = '<div class="suggestion-status">Tidak ada rekomendasi.</div>';
      return;
    }
    box.innerHTML = result.tracks.map((track, index) => `
      <button type="button" class="song-suggestion" data-index="${index}">
        ${track.cover_url ? `<img src="${escapeHtml(track.cover_url)}" alt="">` : '<span class="suggestion-cover"></span>'}
        <span><strong>${escapeHtml(track.title)}</strong><small>${escapeHtml(track.artist)} · ${escapeHtml(track.album)}</small></span>
      </button>`).join('');
    box.querySelectorAll('.song-suggestion').forEach((button, index) => {
      button.addEventListener('click', () => applySongSuggestion(result.tracks[index]));
    });
  } catch {
    box.innerHTML = '<div class="suggestion-status">Rekomendasi gagal dimuat.</div>';
  }
}

function applySongSuggestion(track) {
  form.elements.title.value = track.title || '';
  form.elements.artist.value = track.artist || '';
  form.elements.album.value = track.album || '';
  form.elements.genre.value = track.genre || '';
  form.elements.year.value = track.year || '';
  form.elements.cover_url.value = track.cover_url || '';
  form.elements.description.value = track.description || '';
  const box = document.querySelector('#songSuggestions');
  box.innerHTML = '';
  box.classList.remove('show');
}

async function logout() {
  await api('logout', {});
  location.reload();
}

function openSongModal() {
  form.reset();
  form.elements.id.value = '';
  document.querySelector('#songFormTitle').textContent = 'Tambah lagu';
  dialog.showModal();
}

function closeSongModal() {
  dialog.close();
}

function editSong(song) {
  if (!song) return;
  Object.keys(song).forEach(key => {
    if (form.elements[key]) form.elements[key].value = song[key] ?? '';
  });
  document.querySelector('#songFormTitle').textContent = 'Edit lagu';
  dialog.showModal();
}

async function deleteSong(id) {
  if (!confirm('Hapus lagu ini?')) return;
  try {
    await api('delete-song', { id });
    toast('Lagu dihapus');
    loadSongs();
  } catch (error) {
    toast(error.message);
  }
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  const data = Object.fromEntries(new FormData(form));
  try {
    await api(data.id ? 'update-song' : 'add-song', data);
    closeSongModal();
    toast('Lagu berhasil disimpan');
    loadSongs();
  } catch (error) {
    toast(error.message);
  }
});

// Event delegation for cards, leaderboard, filters, and dialogs
grid.addEventListener('click', event => {
  const editBtn = event.target.closest('.js-edit-song');
  if (editBtn) {
    event.stopPropagation();
    editSong(songsById.get(Number(editBtn.dataset.id)));
    return;
  }
  const delBtn = event.target.closest('.js-delete-song');
  if (delBtn) {
    event.stopPropagation();
    deleteSong(Number(delBtn.dataset.id));
    return;
  }
  const card = event.target.closest('.song-card');
  if (card) openSongDetail(Number(card.dataset.songId));
});

grid.addEventListener('keydown', event => {
  if (event.key !== 'Enter' && event.key !== ' ') return;
  const card = event.target.closest('.song-card');
  if (card && event.target === card) {
    event.preventDefault();
    openSongDetail(Number(card.dataset.songId));
  }
});

topGrid?.addEventListener('click', event => {
  const card = event.target.closest('.top-card');
  if (card) openSongDetail(Number(card.dataset.songId));
});

topGrid?.addEventListener('keydown', event => {
  if (event.key !== 'Enter' && event.key !== ' ') return;
  const card = event.target.closest('.top-card');
  if (card) {
    event.preventDefault();
    openSongDetail(Number(card.dataset.songId));
  }
});

genreFilters?.addEventListener('click', event => {
  const pill = event.target.closest('.genre-pill');
  if (!pill) return;
  currentGenre = pill.dataset.genre || '';
  loadSongs();
});

sortSelect?.addEventListener('change', () => {
  currentSort = sortSelect.value;
  loadSongs();
});

let searchTimer;
search.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = search.value.trim();
    loadSongs();
  }, 250);
});

[dialog, detailDialog].forEach(dlg => {
  dlg?.addEventListener('click', event => {
    if (event.target === dlg) dlg.close();
  });
});

syncCatalog().catch(() => {}).finally(() => loadSongs());
