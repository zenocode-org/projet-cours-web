const API_BASE = '';

const api = {
  async request(url, options = {}) {
    const opts = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      },
      credentials: 'include'
    };
    const res = await fetch(API_BASE + url, opts);
    return res;
  },
  get(url) {
    return this.request(url, { method: 'GET' });
  },
  post(url, data) {
    return this.request(url, { method: 'POST', body: JSON.stringify(data) });
  },
  put(url, data) {
    return this.request(url, { method: 'PUT', body: JSON.stringify(data) });
  },
  delete(url, data) {
    return this.request(url, { method: 'DELETE', body: data ? JSON.stringify(data) : undefined });
  }
};
