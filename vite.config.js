import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    // ¡AQUÍ ESTÁ LA MAGIA!
    server: {
        host: '0.0.0.0', // Le dice a Docker que abra la puerta
        port: 5176,      // Fija el puerto
        hmr: {
            host: 'localhost' // Le dice al navegador: "Búscame en localhost, no en 0.0.0.0"
        }
    }
});