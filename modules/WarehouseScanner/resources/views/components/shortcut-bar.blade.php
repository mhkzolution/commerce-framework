<div class="scanner-shortcut-bar" data-scanner-shortcut-bar aria-hidden="true">
    <span><kbd>F1</kbd> {{ __('warehouse::scanner.modes.stock-check') }}</span>
    <span><kbd>F2</kbd> {{ __('warehouse::scanner.modes.label-attachment') }}</span>
    <span><kbd>F3</kbd> {{ __('warehouse::scanner.modes.receiving') }}</span>
    <span><kbd>F4</kbd> {{ __('warehouse::scanner.modes.picking') }}</span>
    <span><kbd>F5</kbd> {{ __('warehouse::scanner.modes.packing') }}</span>
    <span><kbd>F6</kbd> {{ __('warehouse::scanner.modes.transfer') }}</span>
    <span><kbd>F7</kbd> {{ __('warehouse::scanner.modes.inventory-count') }}</span>
    <span><kbd>Ctrl</kbd>+<kbd>B</kbd> SKU</span>
    <span><kbd>?</kbd> {{ __('warehouse::scanner.shortcuts') }}</span>
</div>

<div class="scanner-shortcut-overlay hidden" data-scanner-shortcut-overlay hidden role="dialog" aria-modal="true" aria-label="{{ __('warehouse::scanner.shortcuts') }}">
    <div class="scanner-shortcut-overlay__panel">
        <h2>{{ __('warehouse::scanner.shortcuts') }}</h2>
        <ul class="scanner-shortcut-overlay__list">
            <li><kbd>F1</kbd>–<kbd>F7</kbd> {{ __('warehouse::scanner.mode') }}</li>
            <li><kbd>F8</kbd> {{ __('warehouse::scanner.dashboard') }}</li>
            <li><kbd>Ctrl</kbd>+<kbd>B</kbd> {{ __('warehouse::scanner.sku') }}</li>
            <li><kbd>1</kbd>–<kbd>9</kbd> {{ __('warehouse::scanner.action') }}</li>
            <li><kbd>Enter</kbd> {{ __('warehouse::scanner.ready') }}</li>
            <li><kbd>Esc</kbd> {{ __('warehouse::scanner.ready') }}</li>
            <li><kbd>+</kbd> / <kbd>−</kbd> {{ __('warehouse::scanner.quantity') }}</li>
        </ul>
        <button type="button" class="scanner-quick-action scanner-quick-action--primary" data-scanner-close-shortcuts>OK</button>
    </div>
</div>
