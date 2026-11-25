(function () {
  const STORAGE_KEY = 'sonora_theme';
  const root = document.documentElement;

  const SUN_ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>';
  const MOON_ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>';

  function getTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light';
    } catch {
      return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }
  }

  function updateToggleButton(theme) {
    const btn = document.querySelector('#themeToggle');
    if (!btn) return;
    const isDark = theme === 'dark';
    btn.innerHTML = `${isDark ? SUN_ICON : MOON_ICON}<span class="theme-label">${isDark ? 'Terang' : 'Gelap'}</span>`;
    btn.setAttribute('aria-label', isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap');
    btn.setAttribute('title', isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap');
    btn.setAttribute('aria-pressed', String(isDark));
  }

  function applyTheme(theme, persist = true) {
    const normalized = theme === 'dark' ? 'dark' : 'light';
    if (normalized === 'dark') {
      root.setAttribute('data-theme', 'dark');
    } else {
      root.removeAttribute('data-theme');
    }
    if (persist) {
      try {
        localStorage.setItem(STORAGE_KEY, normalized);
      } catch {
        // Ignore storage restrictions
      }
    }
    updateToggleButton(normalized);
    window.dispatchEvent(new CustomEvent('sonora:themechange', { detail: { theme: normalized } }));
  }

  function init() {
    applyTheme(getTheme(), false);
    const btn = document.querySelector('#themeToggle');
    if (btn) {
      btn.addEventListener('click', () => {
        applyTheme(getTheme() === 'dark' ? 'light' : 'dark', true);
      });
    }
    requestAnimationFrame(() => {
      root.classList.add('theme-ready');
    });
  }

  window.SonoraTheme = { getTheme, applyTheme };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
