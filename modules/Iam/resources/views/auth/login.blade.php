@extends('iam::layouts.auth')

@section('title', __('iam::auth.sign_in'))
@section('heading', __('iam::auth.sign_in'))

@section('content')
    @if ($errors->any())
        <div class="cf-flash cf-flash--danger admin-auth-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" class="admin-auth-form">
        @csrf

        <div class="admin-auth-field">
            <label class="admin-auth-label" for="email">{{ __('iam::auth.email') }}</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="cf-input"
            >
        </div>

        <div class="admin-auth-field">
            <label class="admin-auth-label" for="password">{{ __('iam::auth.password') }}</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="cf-input"
            >
        </div>

        <label class="admin-auth-remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>{{ __('iam::auth.remember') }}</span>
        </label>

        <button type="submit" class="cf-btn cf-btn--primary admin-auth-submit">
            {{ __('iam::auth.sign_in') }}
        </button>
    </form>

    <p class="admin-auth-footer">
        <a href="{{ route('admin.password.request') }}">{{ __('iam::auth.forgot_password') }}</a>
    </p>
@endsection
