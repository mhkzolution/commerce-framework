@props([
    'lines' => [],
    'totalLabels' => 0,
    'totalProducts' => 0,
])

<section class="bc-queue" aria-label="{{ __('barcode::admin.queue.title') }}">
    <div class="bc-queue__header">
        <h2 class="bc-panel__title">{{ __('barcode::admin.queue.title') }}</h2>
        @if ($totalProducts > 0)
            <button type="button" class="bc-queue__clear" data-bc-clear-queue>
                {{ __('barcode::admin.queue.clear') }}
            </button>
        @endif
    </div>

    <div class="bc-queue__body" data-bc-queue-body>
        @if (count($lines) === 0)
            <div class="bc-queue__empty" data-bc-queue-empty>
                <div class="bc-queue__empty-icon" aria-hidden="true">
                    <x-admin.icon name="tag" class="h-8 w-8" />
                </div>
                <p class="bc-queue__empty-title">{{ __('barcode::admin.queue.empty_title') }}</p>
                <p class="bc-queue__empty-hint">{{ __('barcode::admin.queue.empty_hint') }}</p>
            </div>
        @else
            <ul class="bc-queue__list" role="list" data-bc-queue-list>
                @foreach ($lines as $index => $line)
                    <x-barcode::barcode-queue-item
                        :line-id="$line['id'] ?? $index"
                        :thumbnail-url="$line['thumbnail_url'] ?? null"
                        :owner-name="$line['owner_name'] ?? ''"
                        :product-name="$line['product_name'] ?? ''"
                        :sku="$line['sku'] ?? ''"
                        :quantity="$line['quantity'] ?? 1"
                        :position="$index"
                        :is-first="$index === 0"
                        :is-last="$index === count($lines) - 1"
                    />
                @endforeach
            </ul>
        @endif
    </div>

    <footer class="bc-queue__footer" data-bc-queue-footer @if($totalProducts === 0) hidden @endif>
        <div class="bc-queue__summary">
            <div class="bc-queue__summary-item">
                <span class="bc-queue__summary-label">{{ __('barcode::admin.queue.total_products') }}</span>
                <span class="bc-queue__summary-value" data-bc-total-products>{{ $totalProducts }}</span>
            </div>
            <div class="bc-queue__summary-item bc-queue__summary-item--primary">
                <span class="bc-queue__summary-label">{{ __('barcode::admin.queue.total_labels') }}</span>
                <span class="bc-queue__summary-value" data-bc-total-labels>{{ $totalLabels }}</span>
            </div>
        </div>
    </footer>

    <template id="bc-queue-item-template">
        <x-barcode::barcode-queue-item
            line-id=""
            :thumbnail-url="null"
            owner-name=""
            product-name=""
            sku=""
            :quantity="1"
            :position="0"
            :is-first="false"
            :is-last="false"
            :is-template="true"
        />
    </template>
</section>
