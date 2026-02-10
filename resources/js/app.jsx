import './bootstrap';
import '../css/app.css'; // (Pastikan CSS layout Bootstrap lama dihapus dari file ini)

// HAPUS Impor CSS Bootstrap
// import 'bootstrap/dist/css/bootstrap.min.css'; 
// HAPUS Impor FontAwesome
// import '@fortawesome/fontawesome-free/css/all.min.css';

// TAMBAHKAN Impor Font Roboto
import '@fontsource/roboto/300.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// TAMBAHKAN Impor MUI Theme
import { ThemeProvider, createTheme } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';

// Buat tema dasar (Anda bisa kustomisasi ini nanti)
const theme = createTheme({
    palette: {
        primary: {
            main: '#e6bf3e', // Warna kuning tema Anda
        },
        // Anda bisa menambahkan warna lain di sini
    },
});

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx')
        );

        // PENTING: Jangan set layout default di sini lagi.
        // Biarkan file AuthenticatedLayout.jsx yang di-load oleh halaman
        // (misal Dashboard.jsx) menanganinya.
        
        // HAPUS ATAU KOMENTARI BLOK INI:
        // page.then((module) => {
        //     if (name.startsWith('Auth/')) {
        //         module.default.layout = undefined;
        //     } else {
        //         module.default.layout = module.default.layout || ((page) => <AuthenticatedLayout children={page} />);
        //     }
        //     return module;
        // });

        return page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Hapus window.route jika Anda tidak pakai Ziggy
        
        // BUNGKUS <App> DENGAN THEMEPROVIDER
        root.render(
            <ThemeProvider theme={theme}>
                <CssBaseline /> {/* Menambahkan reset CSS Material Design */}
                <App {...props} />
            </ThemeProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});