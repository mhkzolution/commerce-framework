import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
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
                'resources/js/storefront/shop.js',
                'resources/css/storefront/pdp.css',
                'resources/js/storefront/product.js',
                'resources/css/storefront/shopper.css',
                'resources/js/storefront/shopper.js',
                'resources/css/storefront/auth.css',
                'resources/js/storefront/auth.js',
                'resources/css/storefront/footer.css',
                'resources/js/storefront/home.js',
                'resources/css/barcode.css',
                'resources/js/barcode/index.js',
                'resources/js/barcode/history.js',
                'resources/css/pos.css',
                'resources/js/pos/index.js',
                'resources/css/scanner.css',
                'resources/js/scanner/index.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
