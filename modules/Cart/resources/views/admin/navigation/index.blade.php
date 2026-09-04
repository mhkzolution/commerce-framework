@extends('layouts.admin')

@section('title', 'Storefront navigation')

@section('page')
    <x-admin.page
        title="Storefront navigation"
        description="Curated header links, promo bar, and mega menu structure for the storefront."
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Settings', 'href' => route('admin.settings.index')],
                ['label' => 'Storefront navigation', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card title="Navigation" class="max-w-4xl">
            <form method="POST" action="{{ route('admin.storefront.navigation.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4 rounded-lg border border-border p-4">
                    <h3 class="text-sm font-semibold text-text">Promo bar</h3>

                    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" name="promo_enabled" value="1" @checked(old('promo_enabled', $promoEnabled))>
                        <span>Show promo bar above the header</span>
                    </label>

                    <div>
                        <label class="block text-sm font-medium text-text" for="promo_message">Promo message</label>
                        <input
                            id="promo_message"
                            name="promo_message"
                            type="text"
                            value="{{ old('promo_message', $promoMessage) }}"
                            class="cf-input mt-1"
                            placeholder="FREE SHIPPING ON ORDERS ฿1,500+"
                        >
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" name="promo_dismissible" value="1" @checked(old('promo_dismissible', $promoDismissible))>
                        <span>Allow shoppers to dismiss the promo bar</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text" for="items_json">Navigation items (JSON)</label>
                    <textarea
                        id="items_json"
                        name="items_json"
                        rows="18"
                        class="cf-input mt-1 font-mono text-xs"
                    >{{ old('items_json', $itemsJson) }}</textarea>
                    <p class="mt-2 text-sm text-muted">
                        Configure curated links and mega menu columns. Each item supports
                        <code>type: link</code> or <code>type: mega</code> with catalog sources
                        (<code>categories</code>, <code>collections</code>, <code>brands</code>).
                        Leave empty to use the default config file.
                    </p>
                    @error('items_json')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-admin.button variant="primary" type="submit">Save navigation</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.settings.index')">Back to settings</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection
