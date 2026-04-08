const profile = {
  async load() {
    const res = await api.get('/api/profile.php');
    if (!res.ok) return;
    const data = await res.json();
    document.getElementById('email').value = data.email || '';
    document.getElementById('display_name').value = data.display_name || '';
  },

  async save() {
    const displayName = document.getElementById('display_name').value.trim();
    const password = document.getElementById('password').value;
    const msgEl = document.getElementById('message');
    const errEl = document.getElementById('error');
    msgEl.classList.add('hidden');
    errEl.classList.add('hidden');

    const payload = { display_name: displayName };
    if (password) payload.password = password;

    const res = await api.put('/api/profile.php', payload);
    if (res.ok) {
      msgEl.textContent = 'Profile updated';
      msgEl.classList.remove('hidden');
      document.getElementById('password').value = '';
      setTimeout(() => msgEl.classList.add('hidden'), 3000);
    } else {
      const data = await res.json();
      errEl.textContent = data.error || 'Update failed';
      errEl.classList.remove('hidden');
    }
  }
};

document.getElementById('profile-form').addEventListener('submit', (e) => {
  e.preventDefault();
  profile.save();
});
