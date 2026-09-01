@props([
    'presets' => [],
    'options' => [],
])

<div class="cf-variant-step" data-variant-step="options">
    <div class="cf-variant-step__header">
        <span class="cf-variant-step__number">1</span>
        <div>
            <h3 class="cf-variant-step__title">{{ __('product::workspace.variant_options_step') }}</h3>
            <p class="cf-variant-step__desc">{{ __('product::workspace.variant_options_step_desc') }}</p>
        </div>
    </div>

    <div class="cf-variant-options" data-variant-options>
        <div class="cf-variant-options__list" data-variant-options-list>
            @foreach ($options as $option)
                <div class="cf-variant-option">
                    <span class="cf-variant-option__name">{{ $option['name'] ?? '' }}</span>
                    <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-remove-option="{{ $option['id'] ?? '' }}">{{ __('product::workspace.remove') }}</button>
                </div>
            @endforeach
        </div>

        <div class="cf-variant-options__add">
            <div class="cf-variant-options__preset-row">
                <select class="cf-input" data-variant-option-preset>
                    <option value="">{{ __('product::workspace.add_preset_option') }}</option>
                    @foreach ($presets as $name => $values)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="button" class="cf-btn cf-btn--secondary" data-variant-option-add-preset>
                    {{ __('product::workspace.add_preset') }}
                </button>
            </div>
            <div class="cf-variant-options__custom-row">
                <input
                    type="text"
                    class="cf-input"
                    placeholder="{{ __('product::workspace.add_custom_option') }}"
                    data-variant-option-custom
                >
                <button type="button" class="cf-btn cf-btn--ghost" data-variant-option-add-custom>
                    {{ __('product::workspace.add_custom') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="cf-variant-step" data-variant-step="values">
    <div class="cf-variant-step__header">
        <span class="cf-variant-step__number">2</span>
        <div>
            <h3 class="cf-variant-step__title">{{ __('product::workspace.variant_values_step') }}</h3>
            <p class="cf-variant-step__desc">{{ __('product::workspace.variant_values_step_desc') }}</p>
        </div>
    </div>

    <div class="cf-variant-values" data-variant-values>
        @foreach ($options as $option)
            <div class="cf-variant-values__group">
                <div class="cf-variant-values__label">{{ $option['name'] ?? '' }}</div>
                <div class="cf-variant-values__chips" data-option-chips="{{ $option['id'] ?? '' }}">
                    @foreach ($option['values'] ?? [] as $value)
                        <span class="cf-variant-chip">
                            {{ $value }}
                            <button type="button" class="cf-variant-chip__remove" data-remove-value="{{ $option['id'] ?? '' }}" data-value="{{ $value }}">&times;</button>
                        </span>
                    @endforeach
                </div>
                <div class="cf-variant-values__input-row">
                    <input
                        type="text"
                        class="cf-input"
                        placeholder="{{ __('product::workspace.add_value_placeholder', ['name' => $option['name'] ?? '']) }}"
                        data-option-value-input="{{ $option['id'] ?? '' }}"
                    >
                    <button type="button" class="cf-btn cf-btn--secondary cf-btn--sm" data-option-value-add="{{ $option['id'] ?? '' }}">{{ __('product::workspace.add_value') }}</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
