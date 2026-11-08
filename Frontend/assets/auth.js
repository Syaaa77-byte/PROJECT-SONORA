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
    } catch (e) { error.textContent = e.message; }
  });
}
bindAuth('loginForm', 'login');
bindAuth('registerForm', 'register');
