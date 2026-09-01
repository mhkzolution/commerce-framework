@props([
    'notes' => '',
    'couponCode' => null,
    'promotionName' => null,
])

<section class="pos-right-section" aria-label="ส่วนลดและหมายเหตุ">
    <p class="pos-right-section__title">ส่วนลด & หมายเหตุ</p>
    <div class="mb-3">
        <label for="pos-discount-input" class="mb-1 block text-xs font-semibold text-muted">คูปอง / โค้ดส่วนลด</label>
        <div class="flex gap-2">
            <input type="text" id="pos-discount-input" class="pos-input text-base" placeholder="กรอกรหัสคูปอง..." value="{{ $couponCode }}">
            <button type="button" class="pos-btn pos-btn--secondary" data-pos-action="apply-coupon"><kbd>F3</kbd></button>
            @if ($couponCode)
                <button type="button" class="pos-btn pos-btn--danger pos-btn--icon" data-pos-action="remove-coupon" title="ลบคูปอง">×</button>
            @endif
        </div>
        @if ($promotionName)
            <p class="mt-1 text-xs font-semibold text-primary">{{ $promotionName }}</p>
        @endif
    </div>

    <div>
        <label for="pos-notes-input" class="mb-1 block text-xs font-semibold text-muted">หมายเหตุการขาย</label>
        <textarea
            id="pos-notes-input"
            class="pos-input min-h-[3rem] resize-none text-sm"
            placeholder="บันทึกภายในสำหรับการขายครั้งนี้..."
            rows="2"
        >{{ $notes }}</textarea>
    </div>
</section>
