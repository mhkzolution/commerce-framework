<div class="scanner-action-panel" data-scanner-action-panel>
    <div class="scanner-action-panel__header">
        <h2 class="scanner-action-panel__title" data-scanner-mode-title></h2>
        <p class="scanner-action-panel__status" data-scanner-ready-status>{{ __('warehouse::scanner.ready') }}</p>
    </div>

    <div class="scanner-action-panel__context hidden" data-scanner-mode-context hidden></div>

    <div class="scanner-action-panel__qty hidden" data-scanner-qty-panel hidden>
        <label class="scanner-action-panel__qty-label" for="scanner-qty-input">{{ __('warehouse::scanner.quantity') }}</label>
        <div class="scanner-qty-stepper">
            <button type="button" class="scanner-qty-stepper__btn" data-scanner-qty-dec aria-label="-">−</button>
            <input
                type="number"
                id="scanner-qty-input"
                class="scanner-qty-stepper__input"
                value="1"
                min="1"
                data-scanner-qty-input
            >
            <button type="button" class="scanner-qty-stepper__btn" data-scanner-qty-inc aria-label="+">+</button>
        </div>
    </div>

    <div class="scanner-action-panel__grid" data-scanner-actions></div>
</div>
