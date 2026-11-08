import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // The React SPA (React Router + Redux Toolkit).
            input: 'resources/js/spa/main.jsx',
            refresh: true,
        }),
        react(),
    ],
});
