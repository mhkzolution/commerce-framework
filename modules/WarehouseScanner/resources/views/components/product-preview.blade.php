@props([
    'product' => null,
])

<div class="scanner-preview" data-scanner-preview>
    <div class="scanner-preview__empty" data-scanner-preview-empty>
        <div class="scanner-preview__empty-icon" aria-hidden="true">
            <x-admin.icon name="qr-code" class="h-10 w-10" />
        </div>
        <p class="scanner-preview__empty-text">{{ __('warehouse::scanner.empty_preview') }}</p>
    </div>

    <div class="scanner-preview__content hidden" data-scanner-preview-content hidden>
        <div class="scanner-preview__media">
            <img
                src=""
                alt=""
                class="scanner-preview__image"
                data-scanner-preview-image
                width="160"
                height="160"
            >
            <div class="scanner-preview__image-fallback hidden" data-scanner-preview-fallback aria-hidden="true">
                <x-admin.icon name="photo" class="h-8 w-8" />
            </div>
        </div>

        <div class="scanner-preview__body">
            <p class="scanner-preview__name" data-scanner-preview-name></p>
            <p class="scanner-preview__variant" data-scanner-preview-variant></p>

            <dl class="scanner-preview__meta">
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.owner') }}</dt>
                    <dd data-scanner-preview-owner></dd>
                </div>
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.sku') }}</dt>
                    <dd><code class="scanner-preview__sku" data-scanner-preview-sku></code></dd>
                </div>
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.current_stock') }}</dt>
                    <dd data-scanner-preview-stock></dd>
                </div>
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.location') }}</dt>
                    <dd data-scanner-preview-location></dd>
                </div>
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.shelf') }}</dt>
                    <dd data-scanner-preview-shelf></dd>
                </div>
                <div class="scanner-preview__meta-row">
                    <dt>{{ __('warehouse::scanner.status') }}</dt>
                    <dd><span class="scanner-status-badge" data-scanner-preview-status></span></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
