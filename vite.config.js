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
                'resources/css/pos.css',
                'resources/js/pos/index.js',
                'resources/css/barcode.css',
                'resources/js/barcode/index.js',
                'resources/css/scanner.css',
                'resources/js/scanner/index.js',
                'resources/js/barcode/history.js',
                'resources/js/storefront/shop.js',
                'resources/js/storefront/auth.js',
                'resources/js/storefront/cart.js',
                'resources/js/storefront/checkout.js',
                'resources/js/storefront/blog.js',
                'resources/js/storefront/product.js',
                'resources/js/admin/product-workspace.js',
            ],
            refresh: true,
            fonts: [
                bunny('Prompt', {
                    weights: [300, 400, 500, 600, 700],
                    subsets: ['latin', 'thai'],
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
