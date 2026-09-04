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

        <form method="POST" action="{{ route('admin.settings.website.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.brand') }}</h2>
                </x-slot:header>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="name">{{ __('settings::website.name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $name) }}" required class="cf-input mt-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="description">{{ __('settings::website.description_label') }}</label>
                        <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $description) }}</textarea>
                    </div>

                    @include('media::components.file-attach', [
                        'name' => 'logo_media_uuid',
                        'value' => old('logo_media_uuid', $logoMediaUuid),
                        'label' => __('settings::website.logo'),
                        'imagesOnly' => true,
                    ])
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.contact') }}</h2>
                </x-slot:header>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-text" for="email">{{ __('settings::website.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="cf-input mt-1" placeholder="hello@example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="phone">{{ __('settings::website.phone') }}</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', $phone) }}" class="cf-input mt-1" placeholder="+66 2 123 4567">
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.social') }}</h2>
                </x-slot:header>

                <div class="grid gap-6 sm:grid-cols-2">
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

            <x-admin.card>
                <x-slot:header>
                    <h2 class="text-lg font-medium text-text">{{ __('settings::website.seo') }}</h2>
                </x-slot:header>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="seo_title_suffix">{{ __('settings::website.seo_title_suffix') }}</label>
                        <input id="seo_title_suffix" type="text" name="seo_title_suffix" value="{{ old('seo_title_suffix', $seoTitleSuffix) }}" class="cf-input mt-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="seo_default_description">{{ __('settings::website.seo_default_description') }}</label>
                        <textarea id="seo_default_description" name="seo_default_description" rows="3" class="cf-input mt-1">{{ old('seo_default_description', $seoDefaultDescription) }}</textarea>
                    </div>

                    @include('media::components.file-attach', [
                        'name' => 'seo_og_image_media_uuid',
                        'value' => old('seo_og_image_media_uuid', $seoOgImageMediaUuid),
                        'label' => __('settings::website.seo_og_image'),
                        'imagesOnly' => true,
                    ])
                </div>
            </x-admin.card>

            <x-admin.button variant="primary" type="submit">{{ __('settings::website.save') }}</x-admin.button>
        </form>
    </x-admin.page>
@endsection
