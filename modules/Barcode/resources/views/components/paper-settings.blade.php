@props([
    'paperSizes' => [],
    'settings' => [],
])

@php
    $settings = array_merge([
        'paper_size' => 'a4',
        'rows' => 10,
        'columns' => 4,
        'margin_top' => 10,
        'margin_right' => 10,
        'margin_bottom' => 10,
        'margin_left' => 10,
        'spacing_horizontal' => 2,
        'spacing_vertical' => 2,
        'label_width' => 48.5,
        'label_height' => 25.4,
        'label_orientation' => 'vertical',
        ...config('barcode.label_style', []),
    ], $settings);
    $orientations = [
        'horizontal' => __('barcode::admin.settings.orientation_horizontal'),
        'vertical' => __('barcode::admin.settings.orientation_vertical'),
    ];
@endphp

<section class="bc-paper-settings" data-bc-paper-settings>
    <button type="button" class="bc-paper-settings__toggle" data-bc-settings-toggle aria-expanded="true">
        <span class="bc-panel__title">{{ __('barcode::admin.settings.title') }}</span>
        <x-admin.icon name="chevron-down" class="bc-paper-settings__chevron h-4 w-4" />
    </button>

    <div class="bc-paper-settings__body" data-bc-settings-body>
        <div class="bc-paper-settings__grid">
            <div class="bc-field">
                <label for="bc-paper-size" class="bc-field-label">{{ __('barcode::admin.settings.paper_size') }}</label>
                <select id="bc-paper-size" class="cf-input" data-bc-setting="paper_size">
                    @foreach ($paperSizes as $key => $size)
                        <option value="{{ $key }}" @selected($settings['paper_size'] === $key)>{{ $size['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bc-field">
                <label for="bc-rows" class="bc-field-label">{{ __('barcode::admin.settings.rows') }}</label>
                <input type="number" id="bc-rows" class="cf-input" min="1" max="50" value="{{ $settings['rows'] }}" data-bc-setting="rows">
            </div>

            <div class="bc-field">
                <label for="bc-columns" class="bc-field-label">{{ __('barcode::admin.settings.columns') }}</label>
                <input type="number" id="bc-columns" class="cf-input" min="1" max="20" value="{{ $settings['columns'] }}" data-bc-setting="columns">
            </div>
        </div>

        <fieldset class="bc-paper-settings__fieldset">
            <legend class="bc-field-label">{{ __('barcode::admin.settings.margins') }}</legend>
            <div class="bc-paper-settings__grid bc-paper-settings__grid--4">
                <div class="bc-field">
                    <label for="bc-margin-top" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.margin_top') }}</label>
                    <input type="number" id="bc-margin-top" class="cf-input" min="0" step="0.5" value="{{ $settings['margin_top'] }}" data-bc-setting="margin_top">
                </div>
                <div class="bc-field">
                    <label for="bc-margin-right" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.margin_right') }}</label>
                    <input type="number" id="bc-margin-right" class="cf-input" min="0" step="0.5" value="{{ $settings['margin_right'] }}" data-bc-setting="margin_right">
                </div>
                <div class="bc-field">
                    <label for="bc-margin-bottom" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.margin_bottom') }}</label>
                    <input type="number" id="bc-margin-bottom" class="cf-input" min="0" step="0.5" value="{{ $settings['margin_bottom'] }}" data-bc-setting="margin_bottom">
                </div>
                <div class="bc-field">
                    <label for="bc-margin-left" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.margin_left') }}</label>
                    <input type="number" id="bc-margin-left" class="cf-input" min="0" step="0.5" value="{{ $settings['margin_left'] }}" data-bc-setting="margin_left">
                </div>
            </div>
        </fieldset>

        <fieldset class="bc-paper-settings__fieldset">
            <legend class="bc-field-label">{{ __('barcode::admin.settings.spacing') }}</legend>
            <div class="bc-paper-settings__grid bc-paper-settings__grid--2">
                <div class="bc-field">
                    <label for="bc-spacing-h" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.spacing_horizontal') }}</label>
                    <input type="number" id="bc-spacing-h" class="cf-input" min="0" step="0.5" value="{{ $settings['spacing_horizontal'] }}" data-bc-setting="spacing_horizontal">
                </div>
                <div class="bc-field">
                    <label for="bc-spacing-v" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.spacing_vertical') }}</label>
                    <input type="number" id="bc-spacing-v" class="cf-input" min="0" step="0.5" value="{{ $settings['spacing_vertical'] }}" data-bc-setting="spacing_vertical">
                </div>
            </div>
        </fieldset>

        <fieldset class="bc-paper-settings__fieldset">
            <legend class="bc-field-label">{{ __('barcode::admin.settings.label_size') }}</legend>
            <div class="bc-paper-settings__grid bc-paper-settings__grid--2">
                <div class="bc-field">
                    <label for="bc-label-width" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.label_width') }}</label>
                    <input type="number" id="bc-label-width" class="cf-input" min="1" step="0.1" value="{{ $settings['label_width'] }}" data-bc-setting="label_width">
                </div>
                <div class="bc-field">
                    <label for="bc-label-height" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.settings.label_height') }}</label>
                    <input type="number" id="bc-label-height" class="cf-input" min="1" step="0.1" value="{{ $settings['label_height'] }}" data-bc-setting="label_height">
                </div>
            </div>
        </fieldset>

        <div class="bc-field">
            <label for="bc-label-orientation" class="bc-field-label">{{ __('barcode::admin.settings.label_orientation') }}</label>
            <select id="bc-label-orientation" class="cf-input" data-bc-setting="label_orientation">
                @foreach ($orientations as $value => $label)
                    <option value="{{ $value }}" @selected($settings['label_orientation'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-barcode::label-style-fields :values="$settings" :use-setting-attributes="true" />
    </div>
</section>
