import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: {
        // The prototype uses an inline Vue template, which requires the compiler build.
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/js/prototypes/opportunities.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Kinetics/Tailwind v4 emit directives lightningcss minify does not understand
    build: {
        cssMinify: false,
    },
});
