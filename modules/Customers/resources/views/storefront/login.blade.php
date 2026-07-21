@extends('cart::layouts.storefront')

@section('title', 'Sign in')

@section('content')
    <h1 class="text-2xl font-semibold text-text">Sign in</h1>

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('storefront.account.login.store') }}" class="mt-6 max-w-md space-y-4 rounded-lg border border-border bg-surface p-6 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium text-text" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="password">Password</label>
            <input id="password" type="password" name="password" required class="cf-input mt-1">
        </div>
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="remember" value="1" class="rounded border-border">
            Remember me
        </label>
        <button type="submit" class="cf-btn cf-btn--primary w-full">Sign in</button>
    </form>

    <p class="mt-4 text-sm text-muted">
        No account?
        <a href="{{ route('storefront.account.register') }}" class="text-link underline">Create one</a>
    </p>
@endsection
