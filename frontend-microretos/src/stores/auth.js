import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated = ref(!!localStorage.getItem('admin_token'))

  const login = (token) => {
    localStorage.setItem('admin_token', token)
    isAuthenticated.value = true
  }

  const logout = () => {
    localStorage.removeItem('admin_token')
    isAuthenticated.value = false
  }

  return { isAuthenticated, login, logout }
})
