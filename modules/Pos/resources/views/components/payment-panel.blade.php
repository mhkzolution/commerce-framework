@props([
    'selectedMethod' => null,
    'changeAmount' => null,
    'paymentStatus' => 'unpaid',
])

@php
    $methods = [
        'cash' => 'เงินสด',
        'qr' => 'QR',
        'transfer' => 'โอน',
        'card' => 'บัตร',
        'gift' => 'ของขวัญ',
        'credit' => 'เครดิต',
    ];
@endphp

<section class="pos-right-section" aria-label="การชำระเงิน">
    <p class="pos-right-section__title">วิธีชำระเงิน</p>

    <div class="pos-payment-methods" role="radiogroup" aria-label="เลือกวิธีชำระเงิน">
        @foreach ($methods as $key => $label)
            <button
                type="button"
                class="pos-payment-method {{ $selectedMethod === $key ? 'is-selected' : '' }}"
                role="radio"
                aria-checked="{{ $selectedMethod === $key ? 'true' : 'false' }}"
                data-pos-payment-method="{{ $key }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-3 flex items-center justify-between gap-2">
        <button type="button" class="pos-btn pos-btn--secondary flex-1" data-pos-dialog-open="pos-payment-dialog">
            <kbd>F4</kbd> แบ่งชำระ
        </button>
        <span class="text-xs font-semibold uppercase text-muted">{{ match($paymentStatus) { 'paid' => 'ชำระแล้ว', 'unpaid' => 'ยังไม่ชำระ', 'idle' => 'ว่าง', default => $paymentStatus } }}</span>
    </div>

    @if ($changeAmount !== null)
        <div class="mt-2 flex justify-between rounded-md border border-border bg-background px-3 py-2">
            <span class="text-sm font-semibold">เงินทอน</span>
            <span class="text-lg font-bold">{{ $changeAmount }}</span>
        </div>
    @endif
</section>
