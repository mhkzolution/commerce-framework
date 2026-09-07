@props([
    'product' => null,
    'mode' => 'create',
    'statuses' => [],
])

@php
    $status = old('status', $product?->status ?? 'draft');
    $statusLabel = $statuses[$status] ?? ucfirst($status);
    $type = old('workspace_type', $product?->type ?? 'simple');
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

        <fieldset class="cf-product-workspace__type-switch" aria-label="{{ __('product::workspace.product_type') }}">
            <legend class="sr-only">{{ __('product::workspace.product_type') }}</legend>
            <label>
                <input type="radio" name="workspace_type" value="simple" data-workspace-type @checked($type === 'simple')>
                <span>{{ __('product::workspace.type_simple') }}</span>
            </label>
            <label>
                <input type="radio" name="workspace_type" value="variable" data-workspace-type @checked($type === 'variable')>
                <span>{{ __('product::workspace.type_variable') }}</span>
            </label>
        </fieldset>

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
