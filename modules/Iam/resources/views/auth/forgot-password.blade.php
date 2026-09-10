@extends('iam::layouts.auth')

@section('title', __('iam::auth.forgot_password_title'))
@section('heading', __('iam::auth.forgot_password_title'))
@section('description', __('iam::auth.forgot_password_copy'))

@section('content')
    @if (session('status'))
        <div class="cf-flash cf-flash--info admin-auth-alert" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger admin-auth-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}" class="admin-auth-form">
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

        <button type="submit" class="cf-btn cf-btn--primary admin-auth-submit">
            {{ __('iam::auth.forgot_password_submit') }}
        </button>
    </form>

    <p class="admin-auth-footer">
        <a href="{{ route('admin.login') }}">{{ __('iam::auth.back_to_sign_in') }}</a>
    </p>
@endsection
