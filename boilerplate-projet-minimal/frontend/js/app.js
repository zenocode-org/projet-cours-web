document.getElementById("btn-health")?.addEventListener("click", async () => {
  const out = document.getElementById("out");
  if (!out) return;
  out.classList.remove("hidden");
  out.textContent = "Chargement…";
  try {
    const res = await fetch("/api/healthcheck");
    const text = await res.text();
    out.textContent = `${res.status} ${res.statusText}\n${text}`;
  } catch (e) {
    out.textContent = String(e);
  }
});
