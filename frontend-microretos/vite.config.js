import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
  server: {
  proxy: {
    '/api': {
      // Cambia esto por la URL que usas en tu navegador para ver tu backend
      target: 'http://generador-microretos-dualab.test', 
      changeOrigin: true,
      headers: { Accept: 'application/json' },
    }
  }
}
})