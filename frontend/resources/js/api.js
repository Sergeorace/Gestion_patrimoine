// Client API vers la passerelle Nginx (JWT stocke en localStorage)
const BASE = import.meta.env.VITE_API_URL || '/api-gateway';
export const apiBase = BASE;

export function getToken() { return localStorage.getItem('jwt'); }
export function setToken(t) { localStorage.setItem('jwt', t); }

async function req(method, path, body) {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (res.status === 401) { localStorage.removeItem('jwt'); window.location.href = '/connexion'; throw new Error('Session expirée'); }
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.message || `Erreur ${res.status}`);
  return json.data ?? json;
}

export const api = {
  login: (email, password) => req('POST', '/auth/login', { email, password }),
  get: (p) => req('GET', p),
  post: (p, b) => req('POST', p, b),
  patch: (p, b) => req('PATCH', p, b),
  put: (p, b) => req('PUT', p, b),
};
