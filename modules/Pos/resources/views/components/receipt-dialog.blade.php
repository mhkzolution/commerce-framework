<x-pos::dialog id="pos-receipt-dialog" title="สลิป" :fullscreen="false">
    <div class="mx-auto max-w-sm rounded-lg border border-dashed border-border bg-background p-6 text-center" id="pos-receipt-preview">
        <p class="text-sm text-muted">สลิปจะแสดงที่นี่หลังชำระเงิน</p>
    </div>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>ปิด</button>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-action="print-receipt" data-paper-width="58mm" id="pos-print-receipt-58" disabled>พิมพ์ 58mm</button>
        <button type="button" class="pos-btn pos-btn--primary" data-pos-action="print-receipt" data-paper-width="80mm" id="pos-print-receipt-btn" disabled>พิมพ์ 80mm</button>
    </x-slot:footer>
</x-pos::dialog>
