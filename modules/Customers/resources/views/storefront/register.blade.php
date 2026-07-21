@extends('cart::layouts.storefront')

@section('title', 'Create account')

@section('content')
    <h1 class="text-2xl font-semibold text-text">Create account</h1>

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('storefront.account.register.store') }}" class="mt-6 max-w-md space-y-4 rounded-lg border border-border bg-surface p-6 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium text-text" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone') }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="password">Password</label>
            <input id="password" type="password" name="password" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="cf-input mt-1">
        </div>
        <button type="submit" class="cf-btn cf-btn--primary w-full">Create account</button>
    </form>

    <p class="mt-4 text-sm text-muted">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" class="text-link underline">Sign in</a>
    </p>
@endsection
