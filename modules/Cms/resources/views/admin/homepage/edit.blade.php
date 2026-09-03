@extends('layouts.admin')
@section('title', __('cms::admin.homepage'))
@section('page')
    <x-admin.page :title="__('cms::admin.homepage')" :description="__('cms::admin.homepage_description')">
        <x-admin.form.shell action="{{ route('admin.cms.homepage.update') }}" method="POST" class="max-w-4xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="{{ __('cms::admin.homepage_sections') }}">
                <p class="mb-4 text-sm text-muted">{{ __('cms::admin.homepage_builder_note') }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-muted">
                                <th class="px-2 py-2">{{ __('cms::admin.section') }}</th>
                                <th class="px-2 py-2">{{ __('cms::admin.layout') }}</th>
                                <th class="px-2 py-2">{{ __('cms::admin.sort_order') }}</th>
                                <th class="px-2 py-2">{{ __('cms::admin.grid_columns') }}</th>
                                <th class="px-2 py-2">{{ __('cms::admin.active') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $index => $section)
                                @php
                                    $layoutLocked = ! in_array($section->key, ['promotions', 'arrivals', 'articles'], true);
                                @endphp
                                <tr class="border-b border-border">
                                    <td class="px-2 py-3 font-medium text-text">
                                        <input type="hidden" name="sections[{{ $index }}][uuid]" value="{{ $section->uuid }}">
                                        {{ __('cms::admin.section_'.$section->key) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        @if ($layoutLocked)
                                            <input type="hidden" name="sections[{{ $index }}][layout]" value="{{ $section->layout }}">
                                            <span class="text-muted">{{ $layouts[$section->layout] ?? $section->layout }}</span>
                                        @else
                                            <select name="sections[{{ $index }}][layout]" class="cf-input">
                                                @foreach ($layouts as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('sections.'.$index.'.layout', $section->layout) === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3">
                                        <input name="sections[{{ $index }}][sort_order]" type="number" min="0" class="cf-input w-24" value="{{ old('sections.'.$index.'.sort_order', $section->sort_order) }}">
                                    </td>
                                    <td class="px-2 py-3">
                                        @if ($section->key === 'promotions')
                                            <input name="sections[{{ $index }}][columns]" type="number" min="1" max="4" class="cf-input w-20" value="{{ old('sections.'.$index.'.columns', $section->settings['columns'] ?? 2) }}">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3">
                                        <input type="hidden" name="sections[{{ $index }}][is_active]" value="0">
                                        <input type="checkbox" name="sections[{{ $index }}][is_active]" value="1" @checked(old('sections.'.$index.'.is_active', $section->is_active)) class="rounded border-border">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="primary" type="submit">{{ __('cms::admin.save') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
