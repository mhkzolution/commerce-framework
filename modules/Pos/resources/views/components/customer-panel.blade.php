@props([
    'customer' => null,
    'isGuest' => true,
    'rewardPoints' => null,
    'tier' => null,
    'hasSpecialPricing' => false,
])

<section class="pos-right-section" aria-label="ลูกค้า">
    <p class="pos-right-section__title">ลูกค้า</p>
    <div class="pos-customer">
        <div class="pos-customer__info">
            @if ($isGuest)
                <p class="pos-customer__name">ลูกค้าทั่วไป</p>
                <p class="pos-customer__meta">ยังไม่ได้เลือกลูกค้า</p>
            @else
                <p class="pos-customer__name">{{ $customer['name'] ?? 'ลูกค้า' }}</p>
                <p class="pos-customer__meta">
                    {{ $customer['phone'] ?? $customer['email'] ?? '' }}
                    @if ($tier)
                        · <span class="pos-customer__badge">{{ $tier }}</span>
                    @endif
                </p>
                @if ($rewardPoints !== null)
                    <p class="pos-customer__meta">แต้มสะสม: {{ number_format($rewardPoints) }} แต้ม</p>
                @endif
                @if ($hasSpecialPricing)
                    <p class="pos-customer__meta text-primary font-semibold">ใช้ราคาพิเศษ</p>
                @endif
            @endif
        </div>

        <div class="flex gap-2">
            <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-open="pos-customer-dialog" data-pos-action="customer">
                <kbd>F2</kbd> เลือก
            </button>
            @if (! $isGuest)
                <button type="button" class="pos-btn pos-btn--secondary" data-pos-action="detach-customer" aria-label="ยกเลิกลูกค้า">×</button>
            @endif
        </div>
    </div>
</section>
