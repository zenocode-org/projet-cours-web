const films = {
  debounceTimer: null,

  async loadFilms() {
    const status = document.getElementById('status-filter')?.value || '';
    const favorites = document.getElementById('favorites-filter')?.checked ? '1' : '';
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (favorites) params.set('favorites', favorites);
    const qs = params.toString();
    const res = await api.get('/api/films/index.php' + (qs ? '?' + qs : ''));
    if (!res.ok) return [];
    const data = await res.json();
    return data.films || [];
  },

  async renderGrid() {
    const grid = document.getElementById('films-grid');
    const loading = document.getElementById('loading');
    if (loading) loading.remove();

    const list = await this.loadFilms();
    if (list.length === 0) {
      grid.innerHTML = '<p class="col-span-full text-slate-400 text-center py-12">No films yet. Search above to add some!</p>';
      return;
    }

    grid.innerHTML = list.map(f => `
      <a href="film.html?id=${f.id}" class="group block bg-slate-800 rounded-lg overflow-hidden border border-slate-700 hover:border-amber-500 transition">
        <div class="aspect-[2/3] bg-slate-700 relative">
          ${f.poster_url
            ? `<img src="${f.poster_url}" alt="" class="w-full h-full object-cover">`
            : '<div class="w-full h-full flex items-center justify-center text-slate-500 text-4xl">🎬</div>'}
          <button data-id="${f.id}" data-fav="${f.is_favorite}" class="favorite-btn absolute top-2 right-2 w-8 h-8 rounded-full bg-slate-900/80 flex items-center justify-center text-lg hover:bg-amber-500 transition"
            onclick="event.preventDefault(); films.toggleFavorite(${f.id}, this)">
            ${f.is_favorite ? '❤️' : '🤍'}
          </button>
        </div>
        <div class="p-2">
          <div class="font-medium truncate">${escapeHtml(f.title)}</div>
          <div class="text-sm text-slate-400">${f.year || '-'} ${f.rating != null ? ` · ${f.rating}/10` : ''}</div>
        </div>
      </a>
    `).join('');
  },

  async toggleFavorite(filmId, btn) {
    const isFav = btn.dataset.fav === 'true';
    const url = '/api/favorites/index.php';
    const res = isFav ? await api.delete(url, { film_id: filmId }) : await api.post(url, { film_id: filmId });
    if (res.ok) {
      btn.dataset.fav = !isFav;
      btn.textContent = !isFav ? '❤️' : '🤍';
    }
  },

  init() {
    this.renderGrid();

    document.getElementById('status-filter')?.addEventListener('change', () => this.renderGrid());
    document.getElementById('favorites-filter')?.addEventListener('change', () => this.renderGrid());

    document.getElementById('add-manual-btn')?.addEventListener('click', () => {
      const form = document.getElementById('manual-form');
      form.classList.toggle('hidden');
    });

    document.getElementById('manual-submit')?.addEventListener('click', async () => {
      const title = document.getElementById('manual-title').value.trim();
      if (!title) return;
      const res = await api.post('/api/films/index.php', {
        title,
        year: document.getElementById('manual-year').value.trim() || null,
        poster_url: document.getElementById('manual-poster').value.trim() || null,
        status: 'to_watch'
      });
      if (res.ok) {
        document.getElementById('manual-title').value = '';
        document.getElementById('manual-year').value = '';
        document.getElementById('manual-poster').value = '';
        document.getElementById('manual-form').classList.add('hidden');
        films.renderGrid();
      }
    });

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.searchOMDB(searchInput.value), 300);
      });
    }
  },

  async searchOMDB(query) {
    const resultsEl = document.getElementById('search-results');
    if (!query || query.length < 2) {
      resultsEl.classList.add('hidden');
      return;
    }

    const res = await api.get('/api/films/search.php?q=' + encodeURIComponent(query));
    if (!res.ok) {
      resultsEl.classList.add('hidden');
      return;
    }

    const data = await res.json();
    const items = data.Search || [];
    if (items.length === 0) {
      resultsEl.innerHTML = '<p class="text-slate-400">No results</p>';
      resultsEl.classList.remove('hidden');
      return;
    }

    resultsEl.innerHTML = `
      <div class="bg-slate-800 rounded-lg p-4 border border-slate-700">
        <h3 class="font-semibold mb-3">Search results</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          ${items.map(m => `
            <div class="flex gap-2 items-center bg-slate-700 rounded p-2">
              ${m.Poster && m.Poster !== 'N/A'
                ? `<img src="${m.Poster}" alt="" class="w-12 h-16 object-cover rounded">`
                : '<div class="w-12 h-16 bg-slate-600 rounded flex items-center justify-center text-2xl">🎬</div>'}
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate text-sm">${escapeHtml(m.Title)}</div>
                <div class="text-xs text-slate-400">${m.Year || '-'}</div>
                <button class="add-film-btn mt-1 text-xs px-2 py-0.5 rounded bg-amber-500 hover:bg-amber-600 text-slate-900"
                  data-imdb="${m.imdbID}" data-title="${escapeHtml(m.Title)}" data-year="${m.Year || ''}" data-poster="${m.Poster && m.Poster !== 'N/A' ? m.Poster : ''}">
                  Add
                </button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
    resultsEl.classList.remove('hidden');

    resultsEl.querySelectorAll('.add-film-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const res = await api.post('/api/films/index.php', {
          omdb_id: btn.dataset.imdb,
          title: btn.dataset.title,
          year: btn.dataset.year || null,
          poster_url: btn.dataset.poster || null,
          status: 'to_watch'
        });
        if (res.ok) {
          btn.textContent = 'Added';
          btn.disabled = true;
          films.renderGrid();
        }
      });
    });
  }
};

function escapeHtml(s) {
  const div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}
