<x-pos::dialog id="pos-payment-dialog" title="ชำระเงิน" :fullscreen="true">
    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <p class="mb-3 text-sm font-semibold text-muted">วิธีชำระเงิน</p>
            <div id="pos-mixed-payments-root"></div>
            <button type="button" class="pos-btn pos-btn--secondary mt-3" data-pos-action="add-payment-row">+ เพิ่มวิธีชำระ</button>
        </div>
        <div>
            <p class="mb-3 text-sm font-semibold text-muted">เงินที่รับ (คำนวณเงินทอน)</p>
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg font-bold text-muted">฿</span>
                <input type="text" id="pos-payment-amount" class="pos-input pl-10 text-2xl font-bold" placeholder="0.00" inputmode="decimal">
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2" id="pos-payment-quick-amounts">
                @foreach (['100', '500', '1000'] as $quick)
                    <button type="button" class="pos-btn pos-btn--secondary" data-pos-quick-amount="{{ $quick }}">฿{{ number_format((int) $quick) }}</button>
                @endforeach
                <button type="button" class="pos-btn pos-btn--secondary" data-pos-quick-amount="exact">พอดี</button>
                <button type="button" class="pos-btn pos-btn--secondary" data-pos-quick-amount="clear">ล้าง</button>
            </div>
            <p id="pos-payment-remaining" class="mt-4 text-sm font-semibold text-muted"></p>
        </div>
    </div>

    <p id="pos-payment-feedback" class="pos-payment-feedback" role="alert" hidden></p>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>ยกเลิก</button>
        <button type="button" class="pos-btn pos-btn--primary" id="pos-confirm-payment-btn" data-pos-action="confirm-payment">ยืนยันชำระเงิน</button>
    </x-slot:footer>
</x-pos::dialog>
