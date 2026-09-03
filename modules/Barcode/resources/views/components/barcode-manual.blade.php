@props([
    'sellers' => [],
    'siteName' => '',
])

<section class="bc-manual" data-bc-manual aria-label="{{ __('barcode::admin.manual.title') }}" hidden>
    <div class="bc-manual__header">
        <h2 class="bc-panel__title">{{ __('barcode::admin.manual.title') }}</h2>
        <p class="bc-manual__desc">{{ __('barcode::admin.manual.description') }}</p>
    </div>

    <form class="bc-manual__form" data-bc-manual-form novalidate>
        @if (! empty($sellers))
            <div class="bc-manual__field">
                <label for="bc-manual-seller" class="bc-manual__label">{{ __('barcode::admin.manual.store') }}</label>
                <select id="bc-manual-seller" class="bc-manual__input" data-bc-manual-seller>
                    <option value="">{{ __('barcode::admin.manual.store_default', ['name' => $siteName]) }}</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller['uuid'] }}">{{ $seller['name'] }}</option>
                    @endforeach
                </select>
                <p class="bc-manual__hint">{{ __('barcode::admin.manual.store_hint') }}</p>
            </div>
        @endif

        <div class="bc-manual__field">
            <label for="bc-manual-name" class="bc-manual__label">{{ __('barcode::admin.manual.name') }}</label>
            <input
                type="text"
                id="bc-manual-name"
                class="bc-manual__input"
                data-bc-manual-name
                maxlength="255"
                required
                placeholder="{{ __('barcode::admin.manual.name_placeholder') }}"
            >
        </div>

        <div class="bc-manual__field">
            <label for="bc-manual-barcode" class="bc-manual__label">{{ __('barcode::admin.manual.barcode') }}</label>
            <div class="bc-manual__barcode-row">
                <input
                    type="text"
                    id="bc-manual-barcode"
                    class="bc-manual__input bc-manual__input--mono"
                    data-bc-manual-barcode
                    maxlength="100"
                    required
                    placeholder="{{ __('barcode::admin.manual.barcode_placeholder') }}"
                >
                <button type="button" class="cf-btn cf-btn--secondary cf-btn--sm" data-bc-manual-generate>
                    {{ __('barcode::admin.manual.generate') }}
                </button>
            </div>
            <label class="bc-manual__checkbox">
                <input type="checkbox" data-bc-manual-sequential value="1">
                <span>{{ __('barcode::admin.manual.sequential') }}</span>
            </label>
            <p class="bc-manual__hint" data-bc-manual-sequential-hint hidden>
                {{ __('barcode::admin.manual.sequential_hint') }}
            </p>
        </div>

        <div class="bc-manual__field">
            <label for="bc-manual-sku" class="bc-manual__label">
                {{ __('barcode::admin.manual.sku') }}
                <span class="bc-manual__optional">{{ __('barcode::admin.manual.optional') }}</span>
            </label>
            <input
                type="text"
                id="bc-manual-sku"
                class="bc-manual__input bc-manual__input--mono"
                data-bc-manual-sku
                maxlength="100"
                placeholder="{{ __('barcode::admin.manual.sku_placeholder') }}"
            >
            <p class="bc-manual__hint">{{ __('barcode::admin.manual.sku_hint') }}</p>
        </div>

        <div class="bc-manual__actions">
            <div class="bc-manual__qty-block">
                <span class="bc-manual__qty-label" data-bc-manual-qty-label>{{ __('barcode::admin.manual.quantity') }}</span>
                <div class="bc-qty-stepper" data-bc-manual-qty-wrap>
                <button type="button" class="bc-qty-stepper__btn" data-bc-manual-qty-decrease aria-label="{{ __('barcode::admin.queue.decrease') }}">−</button>
                <input
                    type="number"
                    class="bc-qty-stepper__input"
                    value="1"
                    min="1"
                    data-bc-manual-qty-input
                    aria-label="{{ __('barcode::admin.search.quantity') }}"
                >
                <button type="button" class="bc-qty-stepper__btn" data-bc-manual-qty-increase aria-label="{{ __('barcode::admin.queue.increase') }}">+</button>
                </div>
            </div>
            <button type="submit" class="cf-btn cf-btn--primary" data-bc-manual-add>
                {{ __('barcode::admin.manual.add') }}
            </button>
        </div>
    </form>
</section>
