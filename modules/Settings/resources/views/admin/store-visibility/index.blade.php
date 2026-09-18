@extends('layouts.admin')

@section('title', __('settings::admin.store_visibility_title'))

@php
    use Commerce\Contracts\Storefront\StoreVisibility;

    $options = [
        StoreVisibility::Public->value => [
            'label' => __('settings::admin.store_visibility_mode_public'),
            'hint' => __('settings::admin.store_visibility_hint_public'),
        ],
        StoreVisibility::Catalog->value => [
            'label' => __('settings::admin.store_visibility_mode_catalog'),
            'hint' => __('settings::admin.store_visibility_hint_catalog'),
        ],
        StoreVisibility::Members->value => [
            'label' => __('settings::admin.store_visibility_mode_members'),
            'hint' => __('settings::admin.store_visibility_hint_members'),
        ],
        StoreVisibility::Private->value => [
            'label' => __('settings::admin.store_visibility_mode_private'),
            'hint' => __('settings::admin.store_visibility_hint_private'),
        ],
    ];
@endphp

@section('page')
    <x-admin.page
        :title="__('settings::admin.store_visibility_title')"
        :description="__('settings::admin.store_visibility_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.online_store')],
                ['label' => __('admin::nav.labels.shop_display')],
                ['label' => __('settings::admin.store_visibility'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @session('status')
            <div class="cf-flash cf-flash--success mb-6" role="status">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('admin.settings.store-visibility.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('settings::admin.store_visibility_field')">
                <fieldset class="space-y-3">
                    <legend class="sr-only">{{ __('settings::admin.store_visibility_field') }}</legend>
                    @foreach ($options as $value => $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-4 has-[:checked]:border-primary">
                            <input
                                type="radio"
                                name="visibility"
                                value="{{ $value }}"
                                class="mt-1"
                                @checked(old('visibility', $mode->value) === $value)
                            >
                            <span>
                                <span class="block text-sm font-medium text-text">{{ $option['label'] }}</span>
                                <span class="mt-1 block text-sm text-muted">{{ $option['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                @error('visibility')
                    <p class="mt-3 text-sm text-danger">{{ $message }}</p>
                @enderror
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.store_visibility_save') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
