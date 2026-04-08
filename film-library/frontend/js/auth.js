async function checkAuth() {
  const res = await api.get('/api/auth/me.php');
  if (!res.ok) {
    window.location.href = 'index.html';
    return null;
  }
  return res.json();
}
