function bindAuth(formId, action) {
  const form = document.querySelector(`#${formId}`);
  if (!form) return;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const error = document.querySelector('#formError');
    error.textContent = '';
    try {
      const response = await fetch(`../Backend/api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(Object.fromEntries(new FormData(form)))
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'Terjadi kesalahan');
      location.href = 'index.php';
    } catch (e) {
      error.textContent = e.message;
    }
  });
}

async function handleGoogleCredential(response) {
  const error = document.querySelector('#formError');
  if (error) error.textContent = '';
  try {
    const res = await fetch('../Backend/google_auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ credential: response.credential })
    });
    const result = await res.json();
    if (!res.ok) throw new Error(result.error || 'Login dengan Google gagal.');
    location.href = 'index.php';
  } catch (e) {
    if (error) error.textContent = e.message;
  }
}

function renderGoogleButton() {
  const mount = document.querySelector('#googleBtnMount');
  const fallbackBtn = document.querySelector('#googleFallbackBtn');
  if (!mount || !window.google?.accounts?.id || !window.GOOGLE_CLIENT_ID) return;

  const isDark = window.SonoraTheme?.getTheme() === 'dark';
  const width = Math.min(360, Math.max(240, Math.floor(mount.parentElement?.clientWidth || 360)));
  mount.innerHTML = '';
  window.google.accounts.id.renderButton(mount, {
    type: 'standard',
    theme: isDark ? 'filled_black' : 'outline',
    size: 'large',
    text: 'continue_with',
    shape: 'pill',
    width,
    locale: 'id'
  });
  if (fallbackBtn) fallbackBtn.style.display = 'none';
}

function initGoogleAuth() {
  const fallbackBtn = document.querySelector('#googleFallbackBtn');
  const error = document.querySelector('#formError');

  if (!window.GOOGLE_CLIENT_ID) {
    fallbackBtn?.addEventListener('click', () => {
      if (error) {
        error.textContent = 'Google Login siap digunakan — isi GOOGLE_CLIENT_ID di Backend/config.php terlebih dahulu (lihat panduan singkat di README.md).';
      }
    });
    return;
  }

  let attempts = 0;
  const timer = setInterval(() => {
    attempts++;
    if (window.google?.accounts?.id) {
      clearInterval(timer);
      window.google.accounts.id.initialize({
        client_id: window.GOOGLE_CLIENT_ID,
        callback: handleGoogleCredential
      });
      renderGoogleButton();
    } else if (attempts > 40) {
      clearInterval(timer);
    }
  }, 150);

  fallbackBtn?.addEventListener('click', () => {
    if (window.google?.accounts?.id) {
      window.google.accounts.id.prompt();
    } else if (error) {
      error.textContent = 'Skrip Google Sign-In sedang dimuat atau koneksi terhambat. Silakan coba sesaat lagi.';
    }
  });

  window.addEventListener('sonora:themechange', renderGoogleButton);
}

bindAuth('loginForm', 'login');
bindAuth('registerForm', 'register');
initGoogleAuth();
