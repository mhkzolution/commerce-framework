<x-pos::dialog id="pos-customer-dialog" title="ค้นหาลูกค้า" :fullscreen="true">
    <div class="mb-4">
        <input type="search" id="pos-customer-search-input" class="pos-input" placeholder="ค้นหาด้วยชื่อ, เบอร์โทร, อีเมล, รหัสสมาชิก..." autofocus>
    </div>

    <div id="pos-customer-results" class="space-y-2">
        <p class="text-sm text-muted">พิมพ์เพื่อค้นหาลูกค้า</p>
    </div>

    <div class="mt-4">
        <button type="button" class="pos-btn pos-btn--secondary w-full" data-pos-action="guest-checkout">ขายแบบลูกค้าทั่วไป</button>
    </div>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>ยกเลิก</button>
    </x-slot:footer>
</x-pos::dialog>
