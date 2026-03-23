import axios from 'axios';

// El código detecta automáticamente dónde está abierto el navegador
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

// Si está en tu Mac, busca en local. Si está en internet, dispara al subdominio.
const apiURL = isLocal ? '/api' : 'https://api.dualab.es/api';

const api = axios.create({
  baseURL: apiURL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// Adjunta el token Sanctum en cada petición si existe
api.interceptors.request.use(config => {
  const token = localStorage.getItem('admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;