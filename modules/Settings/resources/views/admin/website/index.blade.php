@extends('layouts.admin')

@section('title', __('settings::website.title'))

@section('page')
    <x-admin.page :title="__('settings::website.title')" :description="__('settings::website.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.configuration')],
                ['label' => __('settings::website.title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form method="POST" action="{{ route('admin.settings.website.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.brand') }}</h2>
                </x-slot:header>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text" for="name">{{ __('settings::website.name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $name) }}" required class="cf-input mt-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="description">{{ __('settings::website.description_label') }}</label>
                        <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $description) }}</textarea>
                    </div>

                    @include('media::components.media-picker', [
                        'name' => 'logo_media_uuid',
                        'value' => old('logo_media_uuid', $logoMediaUuid),
                        'label' => __('settings::website.logo'),
                    ])
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.social') }}</h2>
                </x-slot:header>

                <div class="space-y-4">
                    @foreach (['facebook', 'instagram', 'tiktok', 'line'] as $network)
                        <div>
                            <label class="block text-sm font-medium text-text" for="social-{{ $network }}">{{ __('settings::website.'.$network) }}</label>
                            <input
                                id="social-{{ $network }}"
                                type="text"
                                name="social[{{ $network }}]"
                                value="{{ old('social.'.$network, $social[$network] ?? '') }}"
                                class="cf-input mt-1"
                                placeholder="https://"
                            >
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.button variant="primary" type="submit">{{ __('settings::website.save') }}</x-admin.button>
        </form>
    </x-admin.page>
@endsection
