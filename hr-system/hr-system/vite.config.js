import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // app.jsx = old Inertia pages (being retired); spa/main.jsx = the new React SPA.
            input: ['resources/js/app.jsx', 'resources/js/spa/main.jsx'],
            refresh: true,
        }),
        react(),
    ],
});
