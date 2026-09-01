@props([
    'values' => [],
    'inputPrefix' => '',
    'useSettingAttributes' => false,
])

@php
    $defaults = config('barcode.label_style', []);
    $values = array_merge($defaults, $values);
@endphp

<fieldset class="{{ $useSettingAttributes ? 'bc-paper-settings__fieldset' : '' }}">
    <legend class="{{ $useSettingAttributes ? 'bc-field-label' : 'cf-label' }}">{{ __('barcode::admin.settings.label_content') }}</legend>

    <div class="{{ $useSettingAttributes ? 'bc-paper-settings__grid bc-paper-settings__grid--4' : 'grid gap-4 sm:grid-cols-4' }}">
        @foreach (['top', 'right', 'bottom', 'left'] as $side)
            @php
                $key = "label_padding_{$side}";
                $id = $inputPrefix . $key;
            @endphp
            <div class="{{ $useSettingAttributes ? 'bc-field' : '' }}">
                <label for="{{ $id }}" class="{{ $useSettingAttributes ? 'bc-field-label bc-field-label--sm' : 'text-xs text-muted' }}">
                    {{ __("barcode::admin.settings.label_padding_{$side}") }}
                </label>
                <input
                    type="number"
                    id="{{ $id }}"
                    class="cf-input"
                    min="0"
                    step="0.1"
                    @if ($useSettingAttributes)
                        data-bc-setting="{{ $key }}"
                        value="{{ $values[$key] }}"
                    @else
                        name="{{ $key }}"
                        value="{{ old($key, $values[$key]) }}"
                        required
                    @endif
                >
            </div>
        @endforeach
    </div>

    <div class="{{ $useSettingAttributes ? 'bc-paper-settings__grid bc-paper-settings__grid--3' : 'grid gap-4 sm:grid-cols-3 mt-4' }}">
        @foreach ([
            'label_content_gap' => __('barcode::admin.settings.label_content_gap'),
            'label_owner_font_size' => __('barcode::admin.settings.label_owner_font_size'),
            'label_sku_font_size' => __('barcode::admin.settings.label_sku_font_size'),
        ] as $key => $label)
            @php
                $id = $inputPrefix . $key;
                $isFont = str_ends_with($key, '_font_size');
            @endphp
            <div class="{{ $useSettingAttributes ? 'bc-field' : '' }}">
                <label for="{{ $id }}" class="{{ $useSettingAttributes ? 'bc-field-label bc-field-label--sm' : 'text-xs text-muted' }}">{{ $label }}</label>
                <input
                    type="number"
                    id="{{ $id }}"
                    class="cf-input"
                    min="{{ $isFont ? 1 : 0 }}"
                    @if ($isFont) max="24" @endif
                    step="{{ $isFont ? 0.5 : 0.1 }}"
                    @if ($useSettingAttributes)
                        data-bc-setting="{{ $key }}"
                        value="{{ $values[$key] }}"
                    @else
                        name="{{ $key }}"
                        value="{{ old($key, $values[$key]) }}"
                        required
                    @endif
                >
            </div>
        @endforeach
    </div>
</fieldset>
