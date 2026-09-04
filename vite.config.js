import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/admin/cms-editor.css',
                'resources/js/admin/cms-editor.js',
                'resources/css/storefront/home.css',
                'resources/css/storefront/shop.css',
                'resources/css/storefront/pdp.css',
                'resources/css/storefront/footer.css',
                'resources/js/storefront/home.js',
                'resources/css/barcode.css',
                'resources/js/barcode/index.js',
                'resources/js/barcode/history.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
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
