@props([
    'templates' => [],
    'selectedId' => null,
])

<section class="bc-template-selector" aria-label="{{ __('barcode::admin.templates.title') }}">
    <div class="bc-template-selector__favorites" data-bc-template-favorites>
        @foreach ($templates as $template)
            @if ($template['is_favorite'] ?? false)
                <button
                    type="button"
                    class="bc-template-chip @if(($selectedId ?? null) === $template['id']) bc-template-chip--active @endif"
                    data-bc-template-chip
                    data-template-id="{{ $template['id'] }}"
                >
                    @if ($template['is_default'] ?? false)
                        <span class="bc-template-chip__star" aria-hidden="true">★</span>
                    @endif
                    {{ $template['name'] }}
                </button>
            @endif
        @endforeach
    </div>

    <div class="bc-template-selector__row">
        <label for="bc-template-select" class="bc-field-label">{{ __('barcode::admin.templates.title') }}</label>
        <select id="bc-template-select" class="cf-input" data-bc-template-select>
            @foreach ($templates as $template)
                <option
                    value="{{ $template['id'] }}"
                    @selected(($selectedId ?? null) === $template['id'])
                    data-template='@json($template)'
                >
                    {{ $template['name'] }}
                    @if ($template['is_default'] ?? false) ({{ __('barcode::admin.templates.default') }}) @endif
                </option>
            @endforeach
        </select>
        @can('barcode.template.manage')
            <x-admin.button variant="ghost" :href="route('admin.barcode.templates.index')" class="bc-template-selector__manage">
                {{ __('barcode::admin.templates.title') }}
            </x-admin.button>
        @endcan
    </div>
</section>
