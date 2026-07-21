@extends('layouts.admin')

@section('title', 'Security')

@section('page')
    <x-admin.page title="Account security" description="Manage two-factor authentication, API tokens, and active sessions.">
        @if (session('plain_api_token'))
            <div class="cf-flash cf-flash--info mb-4 max-w-3xl" role="alert">
                New API token (copy now): <code class="font-mono text-sm">{{ session('plain_api_token') }}</code>
            </div>
        @endif

        <x-admin.card title="Two-factor authentication" class="max-w-3xl">
            @if ($twoFactorEnabled)
                <p class="text-sm text-muted">Two-factor authentication is enabled on your account.</p>
                <form method="POST" action="{{ route('admin.iam.security.two-factor.disable') }}" class="mt-4 flex max-w-sm items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="disable_code" class="cf-label">Authentication code</label>
                        <input id="disable_code" name="code" required class="cf-input mt-1">
                    </div>
                    <x-admin.button variant="danger" type="submit">Disable</x-admin.button>
                </form>
            @elseif ($setup)
                <p class="text-sm text-muted">Scan this secret in your authenticator app, then confirm with a code.</p>
                <p class="mt-2 font-mono text-sm">{{ $setup['secret'] }}</p>
                <p class="mt-2 text-xs text-muted break-all">{{ $setup['qr_code_url'] }}</p>
                <form method="POST" action="{{ route('admin.iam.security.two-factor.confirm') }}" class="mt-4 flex max-w-sm items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="confirm_code" class="cf-label">Verification code</label>
                        <input id="confirm_code" name="code" required class="cf-input mt-1">
                    </div>
                    <x-admin.button variant="primary" type="submit">Confirm</x-admin.button>
                </form>
            @else
                <p class="text-sm text-muted">Protect your account with TOTP two-factor authentication.</p>
                <form method="POST" action="{{ route('admin.iam.security.two-factor.enable') }}" class="mt-4">
                    @csrf
                    <x-admin.button variant="primary" type="submit">Enable 2FA</x-admin.button>
                </form>
            @endif
            @error('code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </x-admin.card>

        <x-admin.card title="API tokens" class="mt-6 max-w-3xl">
            <form method="POST" action="{{ route('admin.iam.security.tokens.store') }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="token_name" class="cf-label">Token name</label>
                    <input id="token_name" name="name" value="{{ old('name') }}" required class="cf-input mt-1" placeholder="My integration">
                </div>
                <x-admin.button variant="primary" type="submit">Create token</x-admin.button>
            </form>

            <div class="mt-4 divide-y divide-border">
                @forelse ($tokens as $token)
                    <div class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-text">{{ $token->name }}</p>
                            <p class="text-muted">Created {{ $token->created_at?->format('M j, Y H:i') ?? '—' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.iam.security.tokens.destroy', $token->uuid) }}">
                            @csrf
                            @method('DELETE')
                            <x-admin.button variant="link" type="submit">Revoke</x-admin.button>
                        </form>
                    </div>
                @empty
                    <p class="py-4 text-sm text-muted">No API tokens yet.</p>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card title="Active sessions" class="mt-6 max-w-3xl">
            <div class="divide-y divide-border">
                @forelse ($sessions as $session)
                    <div class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-text">{{ $session['ip_address'] ?? 'Unknown IP' }}</p>
                            <p class="text-muted truncate">{{ $session['user_agent'] ?? 'Unknown device' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.iam.security.sessions.destroy', $session['id']) }}">
                            @csrf
                            @method('DELETE')
                            <x-admin.button variant="link" type="submit">Revoke</x-admin.button>
                        </form>
                    </div>
                @empty
                    <p class="py-4 text-sm text-muted">No database sessions found for this account.</p>
                @endforelse
            </div>
        </x-admin.card>
    </x-admin.page>
@endsection
