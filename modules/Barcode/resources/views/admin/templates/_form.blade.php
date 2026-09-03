@props([
    'template' => null,
])

@php
    $paperSizes = config('barcode.paper_sizes', []);
    $values = $template ?? new \Commerce\Barcode\Models\BarcodeTemplate([
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
        'is_favorite' => false,
        'is_default' => false,
    ]);
    $orientations = [
        'horizontal' => __('barcode::admin.settings.orientation_horizontal'),
        'vertical' => __('barcode::admin.settings.orientation_vertical'),
    ];
@endphp

<div class="space-y-4">
    <div>
        <label class="cf-label" for="name">{{ __('barcode::admin.templates.name') }}</label>
        <input id="name" name="name" class="cf-input" value="{{ old('name', $values->name) }}" required>
    </div>

    <div>
        <label class="cf-label" for="paper_size">{{ __('barcode::admin.settings.paper_size') }}</label>
        <select id="paper_size" name="paper_size" class="cf-input" required>
            @foreach ($paperSizes as $key => $size)
                <option value="{{ $key }}" @selected(old('paper_size', $values->paper_size) === $key)>{{ $size['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="cf-label" for="rows">{{ __('barcode::admin.settings.rows') }}</label>
            <input type="number" id="rows" name="rows" class="cf-input" min="1" max="50" value="{{ old('rows', $values->rows) }}" required>
        </div>
        <div>
            <label class="cf-label" for="columns">{{ __('barcode::admin.settings.columns') }}</label>
            <input type="number" id="columns" name="columns" class="cf-input" min="1" max="20" value="{{ old('columns', $values->columns) }}" required>
        </div>
    </div>

    <fieldset>
        <legend class="cf-label">{{ __('barcode::admin.settings.margins') }}</legend>
        <div class="grid gap-4 sm:grid-cols-4">
            @foreach (['top', 'right', 'bottom', 'left'] as $side)
                <div>
                    <label class="text-xs text-muted" for="margin_{{ $side }}">{{ __("barcode::admin.settings.margin_{$side}") }}</label>
                    <input type="number" id="margin_{{ $side }}" name="margin_{{ $side }}" class="cf-input" min="0" step="0.5" value="{{ old("margin_{$side}", $values->{"margin_{$side}"}) }}" required>
                </div>
            @endforeach
        </div>
    </fieldset>

    <fieldset>
        <legend class="cf-label">{{ __('barcode::admin.settings.spacing') }}</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs text-muted" for="spacing_horizontal">{{ __('barcode::admin.settings.spacing_horizontal') }}</label>
                <input type="number" id="spacing_horizontal" name="spacing_horizontal" class="cf-input" min="0" step="0.5" value="{{ old('spacing_horizontal', $values->spacing_horizontal) }}" required>
            </div>
            <div>
                <label class="text-xs text-muted" for="spacing_vertical">{{ __('barcode::admin.settings.spacing_vertical') }}</label>
                <input type="number" id="spacing_vertical" name="spacing_vertical" class="cf-input" min="0" step="0.5" value="{{ old('spacing_vertical', $values->spacing_vertical) }}" required>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend class="cf-label">{{ __('barcode::admin.settings.label_size') }}</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs text-muted" for="label_width">{{ __('barcode::admin.settings.label_width') }}</label>
                <input type="number" id="label_width" name="label_width" class="cf-input" min="1" step="0.1" value="{{ old('label_width', $values->label_width) }}" required>
            </div>
            <div>
                <label class="text-xs text-muted" for="label_height">{{ __('barcode::admin.settings.label_height') }}</label>
                <input type="number" id="label_height" name="label_height" class="cf-input" min="1" step="0.1" value="{{ old('label_height', $values->label_height) }}" required>
            </div>
        </div>
    </fieldset>

    <div>
        <label class="cf-label" for="label_orientation">{{ __('barcode::admin.settings.label_orientation') }}</label>
        <select id="label_orientation" name="label_orientation" class="cf-input" required>
            @foreach ($orientations as $value => $label)
                <option value="{{ $value }}" @selected(old('label_orientation', $values->label_orientation ?? 'vertical') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <x-barcode::label-style-fields :values="array_merge(config('barcode.label_style', []), [
        'label_padding_top' => old('label_padding_top', $values->label_padding_top),
        'label_padding_right' => old('label_padding_right', $values->label_padding_right),
        'label_padding_bottom' => old('label_padding_bottom', $values->label_padding_bottom),
        'label_padding_left' => old('label_padding_left', $values->label_padding_left),
        'label_content_gap' => old('label_content_gap', $values->label_content_gap),
        'label_owner_font_size' => old('label_owner_font_size', $values->label_owner_font_size),
        'label_sku_font_size' => old('label_sku_font_size', $values->label_sku_font_size),
    ])" />

    <div class="flex flex-wrap gap-4">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_favorite" value="0">
            <input type="checkbox" name="is_favorite" value="1" @checked(old('is_favorite', $values->is_favorite))>
            {{ __('barcode::admin.templates.favorite') }}
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_default" value="0">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $values->is_default))>
            {{ __('barcode::admin.templates.default') }}
        </label>
    </div>
</div>
