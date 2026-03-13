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

export default api;