@props([
    'subtotal' => '฿0.00',
    'discount' => '฿0.00',
    'tax' => '฿0.00',
    'shipping' => '฿0.00',
    'grandTotal' => '฿0.00',
])

<section class="pos-right-section pos-right-section--sticky" aria-label="สรุปยอดชำระ">
    <div class="pos-summary">
        <div class="pos-summary__row">
            <span class="text-muted">ยอดรวม</span>
            <span>{{ $subtotal }}</span>
        </div>
        <div class="pos-summary__row">
            <span class="text-muted">ส่วนลด</span>
            <span class="text-danger">−{{ $discount }}</span>
        </div>
        <div class="pos-summary__row">
            <span class="text-muted">ภาษี</span>
            <span>{{ $tax }}</span>
        </div>
        <div class="pos-summary__row">
            <span class="text-muted">ค่าจัดส่ง</span>
            <span>{{ $shipping }}</span>
        </div>
        <div class="pos-summary__row pos-summary__row--total">
            <span class="pos-summary__total-label">ยอดชำระ</span>
            <span class="pos-summary__total-value" id="pos-grand-total">{{ $grandTotal }}</span>
        </div>
    </div>
</section>
