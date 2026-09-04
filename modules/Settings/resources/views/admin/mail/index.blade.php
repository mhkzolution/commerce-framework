@extends('layouts.admin')

@section('title', __('settings::admin.mail_title'))

@section('page')
    <x-admin.page
        :title="__('settings::admin.mail_title')"
        :description="__('settings::admin.mail_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.mail'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @session('status')
            <div class="cf-flash cf-flash--success mb-6" role="status">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('settings::admin.mail_transport')">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="mail-mailer">{{ __('settings::admin.mail_mailer') }}</label>
                        <select id="mail-mailer" name="mailer" class="cf-input mt-1">
                            @foreach (['smtp' => __('settings::admin.mail_mailer_smtp'), 'log' => __('settings::admin.mail_mailer_log'), 'sendmail' => __('settings::admin.mail_mailer_sendmail')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('mailer', $mailer) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-muted">{{ __('settings::admin.mail_mailer_hint') }}</p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="mail-host">{{ __('settings::admin.mail_host') }}</label>
                            <input id="mail-host" type="text" name="host" value="{{ old('host', $host) }}" class="cf-input mt-1" placeholder="smtp.mailgun.org">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="mail-port">{{ __('settings::admin.mail_port') }}</label>
                            <input id="mail-port" type="number" name="port" value="{{ old('port', $port) }}" class="cf-input mt-1" placeholder="587">
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="mail-username">{{ __('settings::admin.mail_username') }}</label>
                            <input id="mail-username" type="text" name="username" value="{{ old('username', $username) }}" class="cf-input mt-1" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="mail-password">{{ __('settings::admin.mail_password') }}</label>
                            <input id="mail-password" type="password" name="password" value="{{ old('password', $password) }}" class="cf-input mt-1" autocomplete="new-password">
                            <p class="mt-1 text-sm text-muted">{{ __('settings::admin.mail_password_hint') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="mail-encryption">{{ __('settings::admin.mail_encryption') }}</label>
                        <select id="mail-encryption" name="encryption" class="cf-input mt-1">
                            @foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => __('settings::admin.mail_encryption_none')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('encryption', $encryption) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card :title="__('settings::admin.mail_from')">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-text" for="mail-from-address">{{ __('settings::admin.mail_from_address') }}</label>
                        <input id="mail-from-address" type="email" name="from_address" value="{{ old('from_address', $fromAddress) }}" class="cf-input mt-1" placeholder="noreply@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="mail-from-name">{{ __('settings::admin.mail_from_name') }}</label>
                        <input id="mail-from-name" type="text" name="from_name" value="{{ old('from_name', $fromName) }}" class="cf-input mt-1" placeholder="{{ config('app.name') }}">
                    </div>
                </div>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.save_mail') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
