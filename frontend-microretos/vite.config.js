import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '../', '') // lee el .env de la raíz del repo !!

  return {
    plugins: [
      vue(),
      tailwindcss(),
    ],
    server: {
      host: '0.0.0.0',
      port: Number(env.VITE_PORT) || 5173,
      strictPort: true,
      proxy: {
        '/api': {
          target: `http://localhost:${env.APP_PORT || 8000}`,
          changeOrigin: true,
          headers: { Accept: 'application/json' },
        }
      }
    }
  }
})