@props([
    'lineId' => '',
    'thumbnailUrl' => null,
    'ownerName' => '',
    'productName' => '',
    'sku' => '',
    'quantity' => 1,
    'position' => 0,
    'isFirst' => false,
    'isLast' => false,
    'isTemplate' => false,
])

<li
    class="bc-queue-item"
    data-bc-queue-item
    data-line-id="{{ $lineId }}"
    data-position="{{ $position }}"
    @if($isTemplate) data-bc-queue-item-template @endif
>
    @if ($thumbnailUrl)
        <img src="{{ $thumbnailUrl }}" alt="" class="bc-queue-item__thumb" loading="lazy">
    @else
        <div class="bc-queue-item__thumb bc-queue-item__thumb--placeholder" aria-hidden="true">
            <x-admin.icon name="tag" class="h-5 w-5" />
        </div>
    @endif

    <div class="bc-queue-item__body">
        <p class="bc-queue-item__owner">{{ $ownerName }}</p>
        <p class="bc-queue-item__name">{{ $productName }}</p>
        <p class="bc-queue-item__sku">{{ $sku }}</p>

        <div class="bc-queue-item__controls">
            <div class="bc-qty-stepper">
                <button type="button" class="bc-qty-stepper__btn" data-bc-qty-decrease aria-label="{{ __('barcode::admin.queue.decrease') }}">−</button>
                <input
                    type="number"
                    class="bc-qty-stepper__input"
                    value="{{ $quantity }}"
                    min="1"
                    aria-label="{{ __('barcode::admin.search.quantity') }}"
                    data-bc-qty-input
                >
                <button type="button" class="bc-qty-stepper__btn" data-bc-qty-increase aria-label="{{ __('barcode::admin.queue.increase') }}">+</button>
            </div>

            <div class="bc-queue-item__actions">
                <button type="button" class="bc-icon-btn" data-bc-duplicate title="{{ __('barcode::admin.queue.duplicate') }}" aria-label="{{ __('barcode::admin.queue.duplicate') }}">⧉</button>
                <button type="button" class="bc-icon-btn" data-bc-move-up @disabled($isFirst) title="{{ __('barcode::admin.queue.move_up') }}" aria-label="{{ __('barcode::admin.queue.move_up') }}">↑</button>
                <button type="button" class="bc-icon-btn" data-bc-move-down @disabled($isLast) title="{{ __('barcode::admin.queue.move_down') }}" aria-label="{{ __('barcode::admin.queue.move_down') }}">↓</button>
                <button type="button" class="bc-icon-btn bc-icon-btn--danger" data-bc-remove title="{{ __('barcode::admin.queue.remove') }}" aria-label="{{ __('barcode::admin.queue.remove') }}">
                    <x-admin.icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</li>
