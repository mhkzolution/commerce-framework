@props([
    'product' => null,
    'mode' => 'create',
    'statuses' => [],
])

@php
    $status = old('status', $product?->status ?? 'draft');
    $statusLabel = $statuses[$status] ?? ucfirst($status);
@endphp

<header class="cf-product-workspace__header">
    <div class="cf-product-workspace__header-main">
        <div class="min-w-0 flex-1">
            <input
                type="text"
                name="name"
                value="{{ old('name', $product?->name ?? '') }}"
                placeholder="{{ __('product::workspace.product_name_placeholder') }}"
                class="cf-product-workspace__title-input"
                data-workspace-product-name
                required
            >
            <div class="cf-product-workspace__meta">
                <span data-workspace-slug-preview>{{ old('slug', $product?->slug ?? 'product-slug') }}</span>
                <span class="cf-product-workspace__meta-sep" aria-hidden="true">·</span>
                <span data-workspace-save-status>{{ $mode === 'create' ? __('product::workspace.not_saved_yet') : __('product::workspace.saved') }}</span>
            </div>
        </div>

        <div class="cf-product-workspace__header-actions">
            <x-admin.badge variant="{{ $status === 'published' ? 'published' : ($status === 'archived' ? 'archived' : 'draft') }}">
                {{ $statusLabel }}
            </x-admin.badge>

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    </div>
</header>
