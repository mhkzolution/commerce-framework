@extends('layouts.admin')

@section('title', __('settings::admin.auth_title'))

@section('page')
    <x-admin.page
        :title="__('settings::admin.auth_title')"
        :description="__('settings::admin.auth_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.auth'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @session('status')
            <div class="cf-flash cf-flash--success mb-6" role="status">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('admin.settings.auth.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('settings::admin.auth_registration')">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="registration_enabled" value="1" class="mt-1" @checked(old('registration_enabled', $registrationEnabled))>
                    <span>
                        <span class="block text-sm font-medium text-text">{{ __('settings::admin.auth_registration_enable') }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ __('settings::admin.auth_registration_hint') }}</span>
                    </span>
                </label>
            </x-admin.card>

            <x-admin.card :title="__('settings::admin.auth_recaptcha')">
                <div class="space-y-6">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="recaptcha_enabled" value="1" class="mt-1" @checked(old('recaptcha_enabled', $recaptchaEnabled))>
                        <span>
                            <span class="block text-sm font-medium text-text">{{ __('settings::admin.auth_recaptcha_enable') }}</span>
                            <span class="mt-1 block text-sm text-muted">{{ __('settings::admin.auth_recaptcha_enable_hint') }}</span>
                        </span>
                    </label>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="recaptcha-site-key">{{ __('settings::admin.auth_recaptcha_site_key') }}</label>
                            <input id="recaptcha-site-key" type="text" name="recaptcha_site_key" value="{{ old('recaptcha_site_key', $recaptchaSiteKey) }}" class="cf-input mt-1" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="recaptcha-secret-key">{{ __('settings::admin.auth_recaptcha_secret_key') }}</label>
                            <input id="recaptcha-secret-key" type="password" name="recaptcha_secret_key" value="{{ old('recaptcha_secret_key', $recaptchaSecretKey) }}" class="cf-input mt-1" autocomplete="new-password">
                            <p class="mt-1 text-sm text-muted">{{ __('settings::admin.auth_secret_keep_hint') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="recaptcha-min-score">{{ __('settings::admin.auth_recaptcha_min_score') }}</label>
                        <input id="recaptcha-min-score" type="number" name="recaptcha_min_score" value="{{ old('recaptcha_min_score', $recaptchaMinScore) }}" min="0" max="1" step="0.1" class="cf-input mt-1 max-w-xs">
                        <p class="mt-1 text-sm text-muted">{{ __('settings::admin.auth_recaptcha_min_score_hint') }}</p>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card :title="__('settings::admin.auth_line')">
                <div class="space-y-6">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="line_enabled" value="1" class="mt-1" @checked(old('line_enabled', $lineEnabled))>
                        <span>
                            <span class="block text-sm font-medium text-text">{{ __('settings::admin.auth_line_enable') }}</span>
                            <span class="mt-1 block text-sm text-muted">{{ __('settings::admin.auth_line_enable_hint') }}</span>
                        </span>
                    </label>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="line-channel-id">{{ __('settings::admin.auth_line_channel_id') }}</label>
                            <input id="line-channel-id" type="text" name="line_channel_id" value="{{ old('line_channel_id', $lineChannelId) }}" class="cf-input mt-1" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="line-channel-secret">{{ __('settings::admin.auth_line_channel_secret') }}</label>
                            <input id="line-channel-secret" type="password" name="line_channel_secret" value="{{ old('line_channel_secret', $lineChannelSecret) }}" class="cf-input mt-1" autocomplete="new-password">
                            <p class="mt-1 text-sm text-muted">{{ __('settings::admin.auth_secret_keep_hint') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text">{{ __('settings::admin.auth_line_callback_url') }}</label>
                        <input type="text" readonly value="{{ $lineCallbackUrl }}" class="cf-input mt-1 bg-surface text-muted">
                        <p class="mt-1 text-sm text-muted">{{ __('settings::admin.auth_line_callback_hint') }}</p>
                    </div>
                </div>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.save_auth') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
