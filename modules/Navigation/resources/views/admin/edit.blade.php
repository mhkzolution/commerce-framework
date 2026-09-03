@extends('layouts.admin')

@section('title', __('navigation::admin.edit_title', ['name' => $menu->name]))

@section('page')
    <x-admin.page
        :title="__('navigation::admin.edit_title', ['name' => $menu->name])"
        :description="__('navigation::admin.item_hint')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.website')],
                ['label' => __('navigation::admin.title'), 'url' => route('admin.navigation.show')],
                ['label' => $menu->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card>
            <form method="POST" action="{{ route('admin.navigation.menus.update', $menu) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-text" for="menu-name">{{ __('navigation::admin.name') }}</label>
                    <input
                        id="menu-name"
                        type="text"
                        name="name"
                        value="{{ old('name', $menu->name) }}"
                        required
                        class="mt-1 block w-full rounded-md border border-border bg-surface px-3 py-2 text-sm"
                    >
                    <p class="mt-1 text-xs text-muted font-mono">{{ $menu->handle }}</p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-sm font-medium text-text">{{ __('navigation::admin.items') }}</h2>
                        <button type="button" id="navigation-add-item" class="text-sm text-muted hover:text-text hover:underline">
                            {{ __('navigation::admin.add_item') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-muted">
                                    <th class="py-2 pr-3">{{ __('navigation::admin.label') }}</th>
                                    <th class="py-2 pr-3">{{ __('navigation::admin.url') }}</th>
                                    <th class="py-2 pr-3">{{ __('navigation::admin.visible') }}</th>
                                    <th class="py-2">{{ __('navigation::admin.footer_enabled') }}</th>
                                </tr>
                            </thead>
                            <tbody id="navigation-items">
                                @php
                                    $rows = old('items', $menu->items->map(fn ($item) => [
                                        'label' => $item->label,
                                        'url' => $item->url,
                                        'is_visible' => $item->is_visible,
                                        'footer_enabled' => $item->footer_enabled,
                                    ])->all());
                                    if ($rows === []) {
                                        $rows = [['label' => '', 'url' => '', 'is_visible' => true, 'footer_enabled' => true]];
                                    }
                                @endphp
                                @foreach ($rows as $index => $row)
                                    <tr>
                                        <td class="py-2 pr-3">
                                            <input type="text" name="items[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" class="block w-full rounded-md border border-border bg-surface px-3 py-2">
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="text" name="items[{{ $index }}][url]" value="{{ $row['url'] ?? '' }}" class="block w-full rounded-md border border-border bg-surface px-3 py-2">
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="hidden" name="items[{{ $index }}][is_visible]" value="0">
                                            <input type="checkbox" name="items[{{ $index }}][is_visible]" value="1" @checked(filter_var($row['is_visible'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                                        </td>
                                        <td class="py-2">
                                            <input type="hidden" name="items[{{ $index }}][footer_enabled]" value="0">
                                            <input type="checkbox" name="items[{{ $index }}][footer_enabled]" value="1" @checked(filter_var($row['footer_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-admin.button variant="primary" type="submit">{{ __('navigation::admin.save') }}</x-admin.button>
            </form>
        </x-admin.card>
    </x-admin.page>

    <script>
        document.getElementById('navigation-add-item')?.addEventListener('click', function () {
            const body = document.getElementById('navigation-items');
            if (!body) {
                return;
            }

            const index = body.querySelectorAll('tr').length;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="py-2 pr-3">
                    <input type="text" name="items[${index}][label]" value="" class="block w-full rounded-md border border-border bg-surface px-3 py-2">
                </td>
                <td class="py-2 pr-3">
                    <input type="text" name="items[${index}][url]" value="" class="block w-full rounded-md border border-border bg-surface px-3 py-2">
                </td>
                <td class="py-2 pr-3">
                    <input type="hidden" name="items[${index}][is_visible]" value="0">
                    <input type="checkbox" name="items[${index}][is_visible]" value="1" checked>
                </td>
                <td class="py-2">
                    <input type="hidden" name="items[${index}][footer_enabled]" value="0">
                    <input type="checkbox" name="items[${index}][footer_enabled]" value="1" checked>
                </td>
            `;
            body.appendChild(row);
        });
    </script>
@endsection
