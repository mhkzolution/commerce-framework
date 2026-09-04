@extends('layouts.admin')

@section('title', __('settings::admin.appearance_title'))

@section('page')
    <x-admin.page
        :title="__('settings::admin.appearance_title')"
        :description="__('settings::admin.appearance_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.appearance'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form method="POST" action="{{ route('admin.settings.appearance.update') }}" class="max-w-3xl space-y-6" data-theme-appearance-form>
            @csrf
            @method('PUT')

            <x-admin.card :title="__('settings::admin.appearance_preview')">
                <div class="flex flex-wrap gap-3" data-theme-preview>
                    <div class="rounded-lg px-4 py-3 text-sm font-medium shadow-sm transition-colors" data-preview="primary" style="background: var(--color-primary); color: var(--color-on-primary);">
                        {{ __('settings::admin.appearance_color_primary') }}
                    </div>
                    <div class="rounded-lg px-4 py-3 text-sm font-medium shadow-sm transition-colors" data-preview="primary-hover" style="background: var(--color-primary-hover); color: var(--color-on-primary);">
                        {{ __('settings::admin.appearance_preview_hover') }}
                    </div>
                    <div class="rounded-lg border px-4 py-3 text-sm shadow-sm" data-preview="background" style="background: var(--color-background); color: var(--color-text);">
                        {{ __('settings::admin.appearance_color_background') }}
                    </div>
                    <div class="rounded-lg border px-4 py-3 text-sm shadow-sm" data-preview="surface" style="background: var(--color-surface); color: var(--color-text);">
                        {{ __('settings::admin.appearance_color_surface') }}
                    </div>
                    <div class="rounded-lg px-4 py-3 text-sm font-medium shadow-sm transition-colors" data-preview="accent" style="background: var(--color-accent); color: var(--color-on-primary);">
                        {{ __('settings::admin.appearance_color_accent') }}
                    </div>
                    <div class="rounded-lg px-4 py-3 text-sm font-medium shadow-sm transition-colors" data-preview="accent-hover" style="background: var(--color-accent-hover, var(--color-primary-hover)); color: var(--color-on-primary);">
                        {{ __('settings::admin.appearance_color_accent_hover') }}
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Colors">
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ($colors as $key => $color)
                        <div>
                            <label class="block text-sm font-medium text-text" for="color-{{ $key }}">
                                {{ $color['label'] }}
                                <span class="font-mono text-xs text-muted">--color-{{ $color['token'] }}</span>
                            </label>
                            <div class="mt-2 flex items-center gap-3">
                                <input
                                    id="color-{{ $key }}"
                                    type="color"
                                    value="{{ old($key, $color['value']) }}"
                                    class="h-10 w-14 cursor-pointer rounded border border-border bg-surface p-1"
                                    data-color-picker="{{ $key }}"
                                >
                                <input
                                    type="text"
                                    name="{{ $key }}"
                                    value="{{ old($key, $color['value']) }}"
                                    class="cf-input font-mono text-sm"
                                    placeholder="{{ $color['default'] }}"
                                    data-color-hex="{{ $key }}"
                                    pattern="^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})$"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-muted">{{ __('settings::admin.appearance_reset_hint') }}</p>
            </x-admin.card>

            <div class="flex gap-2">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.save') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tokenMap = @json(collect($colors)->mapWithKeys(fn ($color, $key) => [$key => $color['token']]));

                const applyPreview = (key, value) => {
                    const token = tokenMap[key];
                    if (!token || !value) {
                        return;
                    }

                    document.documentElement.style.setProperty(`--color-${token}`, value);

                    const preview = document.querySelector(`[data-preview="${token}"]`);
                    if (preview && ['primary', 'accent', 'primary-hover', 'accent-hover'].includes(token)) {
                        preview.style.background = value;
                    }
                    if (preview && ['background', 'surface'].includes(token)) {
                        preview.style.background = value;
                    }

                    if (key === 'primary' && !document.querySelector('[name="primary_hover"]')?.value) {
                        document.documentElement.style.setProperty('--color-primary-hover', value);
                    }
                    if (key === 'accent' && !document.querySelector('[name="accent_hover"]')?.value) {
                        document.documentElement.style.setProperty('--color-accent-hover', value);
                    }
                };

                document.querySelectorAll('[data-color-picker]').forEach((picker) => {
                    const key = picker.dataset.colorPicker;
                    const hex = document.querySelector(`[data-color-hex="${key}"]`);

                    picker.addEventListener('input', () => {
                        if (hex) {
                            hex.value = picker.value;
                        }
                        applyPreview(key, picker.value);
                    });

                    hex?.addEventListener('input', () => {
                        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
                            picker.value = hex.value;
                            applyPreview(key, hex.value);
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
