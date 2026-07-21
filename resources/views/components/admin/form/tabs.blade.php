@props(['tabs' => []])

<div data-admin-tabs {{ $attributes->merge(['class' => 'border-b border-border']) }}>
    <div class="flex flex-wrap gap-1" role="tablist">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                data-admin-tab="{{ $key }}"
                @class([
                    'cf-tab rounded-t-md px-3 py-2 text-sm font-medium transition',
                    'is-active' => $loop->first,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>

@foreach ($tabs as $key => $label)
    <div data-admin-tab-panel="{{ $key }}" @unless($loop->first) hidden @endunless class="pt-4">
        {{ ${'tab_' . $key} ?? '' }}
    </div>
@endforeach
