@props([
    'lines' => [],
    'itemCount' => 0,
])

<section class="pos-cart" aria-label="ตะกร้าสินค้า">
    <div class="pos-cart__header">
        <span>ตะกร้า</span>
        @if ($itemCount > 0)
            <span class="pos-cart__badge">{{ $itemCount }}</span>
        @endif
    </div>

    @if (count($lines) === 0)
        <div class="pos-cart__empty">
            <div class="pos-cart__empty-icon" aria-hidden="true">🛒</div>
            <p class="pos-cart__empty-text">ยังไม่มีสินค้าในตะกร้า</p>
            <p class="pos-cart__empty-hint">สแกนบาร์โค้ดหรือค้นหาเพื่อเพิ่มสินค้า</p>
        </div>
    @else
        <ul class="pos-cart__lines" role="list">
            @foreach ($lines as $line)
                <x-pos::cart-line
                    :line-id="$line['id'] ?? null"
                    :image-url="$line['image_url'] ?? null"
                    :name="$line['name'] ?? ''"
                    :variant="$line['variant'] ?? ''"
                    :quantity="$line['quantity'] ?? 1"
                    :unit-price="$line['unit_price'] ?? ''"
                    :discount="$line['discount'] ?? ''"
                    :subtotal="$line['subtotal'] ?? ''"
                    :stock-warning="$line['stock_warning'] ?? null"
                />
            @endforeach
        </ul>
    @endif
</section>
