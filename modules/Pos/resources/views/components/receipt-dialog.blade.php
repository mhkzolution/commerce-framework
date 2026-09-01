<x-pos::dialog id="pos-receipt-dialog" title="ใบเสร็จ" :fullscreen="false">
    <div class="mx-auto max-w-sm rounded-lg border border-dashed border-border bg-background p-6 text-center" id="pos-receipt-preview">
        <p class="text-sm text-muted">ใบเสร็จจะแสดงที่นี่หลังชำระเงิน</p>
    </div>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>ปิด</button>
        <button type="button" class="pos-btn pos-btn--primary" data-pos-action="print-receipt" id="pos-print-receipt-btn" disabled>พิมพ์</button>
    </x-slot:footer>
</x-pos::dialog>
