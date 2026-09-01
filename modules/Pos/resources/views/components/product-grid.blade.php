@props([
    'products' => [],
])

<div class="pos-product-zone">
    <div class="pos-product-grid" id="pos-product-results" role="listbox" aria-label="ผลการค้นหาสินค้า">
        @if (count($products) === 0)
            <div class="pos-cart__empty">
                <div class="pos-cart__empty-icon" aria-hidden="true">📦</div>
                <p class="pos-cart__empty-text">ยังไม่มีสินค้าแสดง</p>
                <p class="pos-cart__empty-hint">สแกนบาร์โค้ดหรือค้นหาเพื่อเพิ่มสินค้า</p>
            </div>
        @else
            <div class="pos-product-grid__list">
                @foreach ($products as $product)
                    <x-pos::product-result
                        :product="$product['id'] ?? null"
                        :image-url="$product['image_url'] ?? null"
                        :name="$product['name'] ?? ''"
                        :sku="$product['sku'] ?? ''"
                        :stock="$product['stock'] ?? null"
                        :price="$product['price'] ?? ''"
                        :attributes="$product['attributes'] ?? []"
                        :stock-warning="$product['stock_warning'] ?? null"
                    />
                @endforeach
            </div>
        @endif
    </div>

    <x-pos::product-info />
</div>
