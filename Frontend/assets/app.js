const API = '../Backend/api.php';
const grid = document.querySelector('#songGrid');
const search = document.querySelector('#search');
const dialog = document.querySelector('#songDialog');
const form = document.querySelector('#songForm');

async function api(action, data = null) {
  const options = { credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } };
  if (data) { options.method = 'POST'; options.body = JSON.stringify(data); }
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
  setTimeout(() => box.classList.remove('show'), 2500);
}

async function loadSongs(query = '') {
  try {
    const result = await api(query ? `songs&search=${encodeURIComponent(query)}` : 'songs');
    document.querySelector('#songCount').textContent = `${result.songs.length} lagu`;
    if (!result.songs.length) {
      grid.innerHTML = '<div class="loading">Tidak ada lagu yang cocok.</div>';
      return;
    }

    grid.innerHTML = result.songs.map(song => `
      <article class="song-card">
        <div class="cover">${song.cover_url ? `<img src="${escapeHtml(song.cover_url)}" alt="${escapeHtml(song.title)}">` : escapeHtml(song.title.charAt(0))}</div>
        <div class="song-info">
          <span class="song-meta">${escapeHtml(song.genre || 'Musik')} · ${song.year || '—'} · ${song.rating_count} penilaian</span>
          <h3>${escapeHtml(song.title)}</h3>
          <p class="song-meta">${escapeHtml(song.artist)}</p>
          ${song.album ? `<p class="song-meta">Album: ${escapeHtml(song.album)}</p>` : ''}
          ${song.description ? `<p class="song-description">${escapeHtml(song.description)}</p>` : ''}
          <div class="rating-row">
            <strong>${Number(song.average_rating).toFixed(1)} / 5</strong>
            <div>${[1,2,3,4,5].map(n => `<span class="stars ${n <= Math.round(song.average_rating) ? 'active' : ''}" aria-hidden="true">★</span>`).join('')}</div>
          </div>
          ${window.APP_USER
            ? `<div class="rating-form"><span>Beri ratingmu:</span><div>${[1,2,3,4,5].map(n => `<button class="stars rating-input" onclick="rateSong(${song.id},${n})" aria-label="Beri ${n} bintang">★</button>`).join('')}</div></div>`
            : `<p class="login-rating"><a href="login.php">Silakan Login terlebih dahulu untuk memberikan rating</a></p>`}
          ${window.APP_USER?.role === 'admin' ? `<div class="card-actions"><button class="text-btn" onclick='editSong(${JSON.stringify(song)})'>Edit</button><button class="text-btn danger" onclick="deleteSong(${song.id})">Hapus</button></div>` : ''}
        </div>
      </article>`).join('');
  } catch (error) { grid.innerHTML = `<div class="loading">${error.message}</div>`; }
}

async function loadCatalog(query = '') {
  const catalogGrid = document.querySelector('#catalogGrid');
  try {
    await syncCatalog();
    const result = await api(query ? `songs&search=${encodeURIComponent(query)}` : 'songs');
    catalogGrid.innerHTML = result.songs.length
      ? result.songs.map(track => `
      <article class="song-card">
        <div class="cover">${track.cover_url ? `<img src="${escapeHtml(track.cover_url)}" alt="${escapeHtml(track.title)}">` : escapeHtml(track.title.charAt(0))}</div>
        <div class="song-info">
          <span class="song-meta">${escapeHtml(track.genre || 'Musik')} · ${track.year || '—'}</span>
          <h3>${escapeHtml(track.title)}</h3>
          <p class="song-meta">${escapeHtml(track.artist)}</p>
          <p class="song-meta">${escapeHtml(track.album)}</p>
          ${track.description ? `<p class="song-description">${escapeHtml(track.description)}</p>` : ''}
          <strong>Rata-rata: ${Number(track.average_rating).toFixed(1)} / 5</strong>
        </div>
      </article>`).join('')
      : '<div class="loading">Tidak ada lagu di katalog yang cocok.</div>';
  } catch (error) {
    catalogGrid.innerHTML = `<div class="loading">${error.message}</div>`;
  }
}

async function syncCatalog() {
  const response = await fetch('../Backend/catalog.php', { credentials: 'same-origin' });
  const contentType = response.headers.get('content-type') || '';
  if (!response.ok || !contentType.includes('application/json')) {
    throw new Error('Katalog belum dapat disinkronkan. Periksa database terlebih dahulu.');
  }
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

function escapeHtml(value) {
  const node = document.createElement('div');
  node.textContent = value ?? '';
  return node.innerHTML;
}

async function rateSong(songId, score) {
  if (!window.APP_USER) { location.href = 'login.php'; return; }
  try { await api('rate', { song_id: songId, score }); toast(`Rating ${score}/5 tersimpan`); loadSongs(search.value); }
  catch (error) { toast(error.message); }
}

async function logout() { await api('logout', {}); location.reload(); }

function openSongModal() { form.reset(); form.elements.id.value = ''; document.querySelector('#songFormTitle').textContent = 'Tambah lagu'; dialog.showModal(); }
function closeSongModal() { dialog.close(); }
function editSong(song) {
  Object.keys(song).forEach(key => { if (form.elements[key]) form.elements[key].value = song[key] ?? ''; });
  document.querySelector('#songFormTitle').textContent = 'Edit lagu'; dialog.showModal();
}
async function deleteSong(id) {
  if (!confirm('Hapus lagu ini?')) return;
  try { await api('delete-song', { id }); toast('Lagu dihapus'); loadSongs(search.value); } catch (error) { toast(error.message); }
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  const data = Object.fromEntries(new FormData(form));
  try {
    await api(data.id ? 'update-song' : 'add-song', data);
    closeSongModal(); toast('Lagu berhasil disimpan'); loadSongs(search.value);
  } catch (error) { toast(error.message); }
});

let searchTimer;
search.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    loadSongs(search.value);
    loadCatalog(search.value);
  }, 300);
});
loadSongs();
loadCatalog();
