@extends('layouts.admin')

@section('title', 'Settings')

@section('page')
    <x-admin.page title="Settings" description="System configuration.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Settings', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <div class="space-y-8">
            @foreach ($structure as $section)
                @php
                    /** @var \Commerce\Settings\Models\SettingGroup $group */
                    $group = $section['group'];
                    $settings = $section['settings'];
                @endphp

                <x-admin.card>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-text">{{ $group->label }}</h2>
                                @if ($group->description)
                                    <p class="text-sm text-muted">{{ $group->description }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.settings.reset', $group->code) }}">
                                @csrf
                                <button type="submit" class="text-sm text-muted hover:text-text hover:underline">Reset defaults</button>
                            </form>
                        </div>
                    </x-slot:header>

                    <form method="POST" action="{{ route('admin.settings.update', $group->code) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        @foreach ($settings as $setting)
                            @php
                                $label = $setting->meta['label'] ?? \Illuminate\Support\Str::headline($setting->key);
                                $value = $setting->value ?? $setting->default_value;
                            @endphp

                            <div>
                                <label class="block text-sm font-medium text-text" for="setting-{{ $group->code }}-{{ $setting->key }}">
                                    {{ $label }}
                                </label>

                                @if ($setting->type === 'boolean')
                                    <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                    <input
                                        id="setting-{{ $group->code }}-{{ $setting->key }}"
                                        type="checkbox"
                                        name="settings[{{ $setting->key }}]"
                                        value="1"
                                        @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))
                                        class="mt-2 rounded border-border"
                                    >
                                @elseif ($setting->type === 'textarea')
                                    <textarea
                                        id="setting-{{ $group->code }}-{{ $setting->key }}"
                                        name="settings[{{ $setting->key }}]"
                                        rows="3"
                                        class="cf-input mt-1"
                                    >{{ $value }}</textarea>
                                @else
                                    <input
                                        id="setting-{{ $group->code }}-{{ $setting->key }}"
                                        type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                        name="settings[{{ $setting->key }}]"
                                        value="{{ $value }}"
                                        class="cf-input mt-1"
                                    >
                                @endif
                            </div>
                        @endforeach

                        <div class="pt-2">
                            <x-admin.button type="submit" variant="primary">
                                Save {{ $group->label }}
                            </x-admin.button>
                        </div>
                    </form>
                </x-admin.card>
            @endforeach
        </div>
    </x-admin.page>
@endsection
