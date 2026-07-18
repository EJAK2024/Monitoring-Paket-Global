import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/dashboard.js',
                'resources/js/viz.js',
                'resources/js/currency.js',
                'resources/js/portmap.js',
                'resources/js/watchlist.js',
                'resources/js/admin.js',
                'resources/js/supplier.js',
                'resources/js/container.js',
                'resources/js/alert.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['chart.js', 'leaflet', 'leaflet.markercluster'],
                },
            },
        },
        target: 'es2020',
        minify: 'esbuild',
        cssMinify: true,
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
