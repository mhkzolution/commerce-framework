@extends('iam::layouts.auth')

@section('title', __('iam::auth.two_factor_title'))
@section('heading', __('iam::auth.two_factor_title'))
@section('description', __('iam::auth.two_factor_copy'))

@section('content')
    @if ($errors->any())
        <div class="cf-flash cf-flash--danger admin-auth-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.two-factor.submit') }}" class="admin-auth-form">
        @csrf

        <div class="admin-auth-field">
            <label class="admin-auth-label" for="code">{{ __('iam::auth.two_factor_code') }}</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
                class="cf-input"
            >
        </div>

        <button type="submit" class="cf-btn cf-btn--primary admin-auth-submit">
            {{ __('iam::auth.two_factor_submit') }}
        </button>
    </form>
@endsection
