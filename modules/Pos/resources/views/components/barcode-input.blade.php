@props([
    'autofocus' => false,
])

<div class="pos-search-zone">
    <label for="pos-barcode-input" class="pos-search-zone__label">สแกน SKU</label>
    <input
        type="text"
        id="pos-barcode-input"
        class="pos-input pos-input--barcode"
        placeholder="สแกนหรือพิมพ์ SKU..."
        autocomplete="off"
        data-pos-barcode-input
        @if($autofocus) autofocus @endif
    >
    <p class="pos-input-hint">สแกนได้ทันทีโดยไม่ต้องคลิกช่องนี้ · <kbd>Ctrl</kbd>+<kbd>B</kbd> โฟกัส</p>
</div>
