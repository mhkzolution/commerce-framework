@extends('layouts.admin')

@section('title', 'Product settings')

@section('page')
    <x-admin.page
        title="Product settings"
        description="Defaults used across the product workspace and storefront."
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'href' => route('admin.products.index')],
                ['label' => 'Settings', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card title="Defaults" class="max-w-2xl">
            <form method="POST" action="{{ route('admin.products.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('media::components.file-attach', [
                    'name' => 'fallback_image_media_uuid',
                    'value' => old('fallback_image_media_uuid', $fallbackImageMediaUuid),
                    'label' => 'Fallback image',
                    'imagesOnly' => true,
                    'help' => 'Upload a new image, import from URL, or pick from the media library.',
                ])

                <div>
                    <label class="block text-sm font-medium text-text" for="sku_pattern">Default SKU pattern</label>
                    <input
                        id="sku_pattern"
                        name="sku_pattern"
                        type="text"
                        value="{{ old('sku_pattern', $skuPattern) }}"
                        class="cf-input mt-1"
                        placeholder="{PRODUCT}-{COLOR}-{SIZE}"
                    >
                    <p class="mt-1 text-sm text-muted">
                        Used when generating variant SKUs. Tokens: <code>{PRODUCT}</code>, option names like <code>{COLOR}</code>.
                    </p>
                    @error('sku_pattern')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-admin.button variant="primary" type="submit">Save settings</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.products.index')">Back to products</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection
