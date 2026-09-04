@extends('layouts.admin')

@section('title', __('settings::admin.site_identity_title'))

@section('page')
    <x-admin.page
        :title="__('settings::admin.site_identity_title')"
        :description="__('settings::admin.site_identity_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.site_identity'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form method="POST" action="{{ route('admin.settings.site-identity.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('settings::admin.site_identity')">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="site-name">{{ __('settings::admin.site_name') }}</label>
                        <input
                            id="site-name"
                            type="text"
                            name="name"
                            value="{{ old('name', $siteName) }}"
                            class="cf-input mt-1"
                            placeholder="{{ config('commerce.name', 'Commerce Framework') }}"
                        >
                        <p class="mt-1 text-sm text-muted">{{ __('settings::admin.site_name_hint') }}</p>
                    </div>

                    @include('media::components.file-attach', [
                        'name' => 'logo_media_uuid',
                        'value' => old('logo_media_uuid', $logoMediaUuid),
                        'label' => __('settings::admin.site_logo'),
                        'imagesOnly' => true,
                        'help' => __('settings::admin.site_logo_hint'),
                    ])

                    @include('media::components.file-attach', [
                        'name' => 'favicon_media_uuid',
                        'value' => old('favicon_media_uuid', $faviconMediaUuid),
                        'label' => __('settings::admin.favicon'),
                        'imagesOnly' => true,
                        'help' => __('settings::admin.favicon_hint'),
                    ])
                </div>
            </x-admin.card>

            <x-admin.card :title="__('settings::admin.contact_social')">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="contact-address">{{ __('settings::admin.contact_address') }}</label>
                        <textarea
                            id="contact-address"
                            name="contact_address"
                            rows="3"
                            class="cf-input mt-1"
                            placeholder="123 Sukhumvit Rd, Bangkok 10110"
                        >{{ old('contact_address', $contactAddress) }}</textarea>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="contact-email">{{ __('settings::admin.contact_email') }}</label>
                            <input
                                id="contact-email"
                                type="email"
                                name="contact_email"
                                value="{{ old('contact_email', $contactEmail) }}"
                                class="cf-input mt-1"
                                placeholder="hello@example.com"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text" for="contact-phone">{{ __('settings::admin.contact_phone') }}</label>
                            <input
                                id="contact-phone"
                                type="text"
                                name="contact_phone"
                                value="{{ old('contact_phone', $contactPhone) }}"
                                class="cf-input mt-1"
                                placeholder="+66 2 123 4567"
                            >
                        </div>
                    </div>

                    <div class="border-t border-border pt-6">
                        <p class="mb-4 text-sm font-medium text-text">{{ __('settings::admin.social_profiles') }}</p>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-text" for="social-facebook">{{ __('settings::admin.social_facebook') }}</label>
                                <input
                                    id="social-facebook"
                                    type="url"
                                    name="social_facebook"
                                    value="{{ old('social_facebook', $socialFacebook) }}"
                                    class="cf-input mt-1"
                                    placeholder="https://facebook.com/yourpage"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text" for="social-instagram">{{ __('settings::admin.social_instagram') }}</label>
                                <input
                                    id="social-instagram"
                                    type="url"
                                    name="social_instagram"
                                    value="{{ old('social_instagram', $socialInstagram) }}"
                                    class="cf-input mt-1"
                                    placeholder="https://instagram.com/yourpage"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text" for="social-tiktok">{{ __('settings::admin.social_tiktok') }}</label>
                                <input
                                    id="social-tiktok"
                                    type="url"
                                    name="social_tiktok"
                                    value="{{ old('social_tiktok', $socialTiktok) }}"
                                    class="cf-input mt-1"
                                    placeholder="https://tiktok.com/@yourpage"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text" for="social-line">{{ __('settings::admin.social_line') }}</label>
                                <input
                                    id="social-line"
                                    type="text"
                                    name="social_line"
                                    value="{{ old('social_line', $socialLine) }}"
                                    class="cf-input mt-1"
                                    placeholder="https://line.me/R/ti/p/@yourid"
                                >
                                <p class="mt-1 text-sm text-muted">{{ __('settings::admin.social_line_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.save_site_identity') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
