import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api.js'

// Duración del token en minutos (debe coincidir con config/sanctum.php → expiration)
const TOKEN_DURATION_MINUTES = 1440

export const useAuthStore = defineStore('auth', () => {
  // Limpiar token caducado o sin timestamp al inicializar la store,
  // evitando que un token antiguo en localStorage supere el guard de navegación.
  const _initToken     = localStorage.getItem('admin_token')
  const _initCreatedAt = Number(localStorage.getItem('admin_token_created_at') || 0)
  const _isExpiredOnLoad = _initToken && (!_initCreatedAt ||
    (Date.now() - _initCreatedAt) / 1000 / 60 >= TOKEN_DURATION_MINUTES)
  if (_isExpiredOnLoad) {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_token_created_at')
  }

  const isAuthenticated = ref(!!localStorage.getItem('admin_token'))
  const refreshing = ref(false)

  // Reloj reactivo: se actualiza cada minuto para que minutosRestantes recalcule solo
  const now = ref(Date.now())
  setInterval(() => { now.value = Date.now() }, 60_000)

  // Sincronizar si el interceptor de api.js limpia el token por 401
  const onTokenExpired = () => { isAuthenticated.value = false }
  window.addEventListener('auth:token-expired', onTokenExpired)

  // Minutos restantes antes de que expire el token (-1 = no hay sesión)
  const minutosRestantes = computed(() => {
    const createdAt = localStorage.getItem('admin_token_created_at')
    if (!createdAt || !isAuthenticated.value) return -1
    const elapsed = (now.value - Number(createdAt)) / 1000 / 60
    return Math.max(0, Math.floor(TOKEN_DURATION_MINUTES - elapsed))
  })

  const login = (token) => {
    localStorage.setItem('admin_token', token)
    localStorage.setItem('admin_token_created_at', String(Date.now()))
    // Inicializar el timer de seguridad de BD: el login cuenta como verificación
    sessionStorage.setItem('db_security_verified_at', String(Date.now()))
    isAuthenticated.value = true
  }

  const logout = () => {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_token_created_at')
    isAuthenticated.value = false
  }

  // Rota el token actual por uno nuevo sin pedir contraseña
  const refresh = async () => {
    if (refreshing.value) return
    refreshing.value = true
    try {
      const { data } = await api.post('/admin/refresh')
      login(data.token)
    } finally {
      refreshing.value = false
    }
  }

  return { isAuthenticated, minutosRestantes, refreshing, login, logout, refresh }
})
