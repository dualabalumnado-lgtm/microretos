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

// Si el servidor responde 401, el token ha expirado: limpiar sesión local.
// Excepción: /empresas/verificar-acceso devuelve 401 para contraseña de módulo
// incorrecta — no es un error de sesión y no debe cerrar la sesión del usuario.
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      // Estas rutas devuelven 401 por contraseña de módulo incorrecta, no por sesión expirada.
      // Excluirlas evita que un error de contraseña cierre la sesión del usuario.
      const esVerificacionLocal = url.includes('/empresas/verificar-acceso') ||
                                   url.includes('/admin/verify-password')
      if (!esVerificacionLocal) {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_token_created_at');
        // Notificar a los componentes que escuchen este evento
        window.dispatchEvent(new CustomEvent('auth:token-expired'));
      }
    }
    return Promise.reject(error);
  }
);

export default api;