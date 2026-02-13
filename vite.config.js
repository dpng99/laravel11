import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        cors: true,      // Agar server bisa diakses dari luar container (Wajib untuk Docker)
        port: 5173,           // Port default Vite
        hmr: {
            host: 'localhost', // Memaksa browser konek ke 'localhost' (IPv4), BUKAN '[::]' (IPv6)
        },
        watch: {
            usePolling: true, // Wajib agar perubahan file terdeteksi di Windows
        },
    },
});
