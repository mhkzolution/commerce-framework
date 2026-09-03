@props([
    'template' => null,
])

@php
    $presets = config('barcode.presets', []);
    $values = $template ?? new \Commerce\Barcode\Models\BarcodeTemplate([
        'preset_code' => 'a4_40',
        'label_orientation' => 'vertical',
        'show_name' => true,
        'show_sku' => true,
        'show_owner' => true,
        'show_barcode' => true,
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
        <label class="cf-label" for="preset_code">{{ __('barcode::admin.templates.preset') }}</label>
        <select id="preset_code" name="preset_code" class="cf-input" required>
            @foreach ($presets as $code => $preset)
                <option value="{{ $code }}" @selected(old('preset_code', $values->preset_code ?? 'a4_40') === $code)>
                    {{ $preset['name'] ?? $code }}
                </option>
            @endforeach
        </select>
    </div>

    <fieldset>
        <legend class="cf-label">{{ __('barcode::admin.templates.visibility') }}</legend>
        <div class="flex flex-wrap gap-4">
            @foreach (['show_name', 'show_sku', 'show_owner', 'show_barcode'] as $flag)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="{{ $flag }}" value="0">
                    <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $values->{$flag} ?? true))>
                    {{ __("barcode::admin.templates.{$flag}") }}
                </label>
            @endforeach
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
