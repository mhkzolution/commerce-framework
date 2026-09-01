<x-pos::dialog id="pos-product-search-dialog" title="Product search" :fullscreen="true">
    <x-pos::search-bar />
    <div class="mt-4">
        <x-pos::product-grid :products="[]" />
    </div>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>Close</button>
    </x-slot:footer>
</x-pos::dialog>
