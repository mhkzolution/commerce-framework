<x-pos::dialog id="pos-hold-dialog" title="บิลที่พักไว้" :fullscreen="false">
    <div id="pos-hold-list" class="space-y-2">
        <p class="text-sm text-muted">ไม่มีบิลที่พักไว้</p>
    </div>

    <x-slot:footer>
        <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-close>ปิด</button>
    </x-slot:footer>
</x-pos::dialog>
