import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// Duración del token en minutos (debe coincidir con config/sanctum.php → expiration)
const TOKEN_DURATION_MINUTES = 1440

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated = ref(!!localStorage.getItem('admin_token'))

  // Minutos restantes antes de que expire el token (-1 = no hay sesión)
  const minutosRestantes = computed(() => {
    const createdAt = localStorage.getItem('admin_token_created_at')
    if (!createdAt || !isAuthenticated.value) return -1
    const elapsed = (Date.now() - Number(createdAt)) / 1000 / 60
    return Math.max(0, Math.floor(TOKEN_DURATION_MINUTES - elapsed))
  })

  const login = (token) => {
    localStorage.setItem('admin_token', token)
    localStorage.setItem('admin_token_created_at', String(Date.now()))
    isAuthenticated.value = true
  }

  const logout = () => {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_token_created_at')
    isAuthenticated.value = false
  }

  return { isAuthenticated, minutosRestantes, login, logout }
})
