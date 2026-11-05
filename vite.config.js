import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/theme.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('Dela Gothic One', {
                    weights: [400],
                }),
                bunny('Yomogi', {
                    weights: [400],
                }),
                bunny('Caveat', {
                    weights: [500, 600],
                }),
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Noto Sans JP', {
                    weights: [400, 500, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
