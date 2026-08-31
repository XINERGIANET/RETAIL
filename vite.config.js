import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'], // 👈 SOLO JS
            refresh: true,
        }),
        tailwindcss(),
    ],
});
