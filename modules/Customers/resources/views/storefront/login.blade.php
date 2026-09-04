@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.sign_in'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container variant="narrow" class="storefront-shopper storefront-auth">
        <h1 class="storefront-shopper__title">{{ __('storefront::storefront.sign_in') }}</h1>

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('storefront.account.login.store') }}" class="storefront-panel storefront-stack">
            @csrf
            <div class="storefront-field">
                <label class="storefront-field__label" for="email">{{ __('storefront::storefront.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="storefront-input">
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="password">{{ __('storefront::storefront.password') }}</label>
                <input id="password" type="password" name="password" required class="storefront-input">
            </div>
            <label class="storefront-check">
                <input type="checkbox" name="remember" value="1">
                {{ __('storefront::storefront.remember_me') }}
            </label>
            <button type="submit" class="storefront-btn storefront-btn--block">{{ __('storefront::storefront.sign_in') }}</button>
        </form>

        <p class="storefront-muted">
            {{ __('storefront::storefront.no_account') }}
            <a href="{{ route('storefront.account.register') }}" class="storefront-link">{{ __('storefront::storefront.create_one') }}</a>
        </p>
    </x-storefront.layout.page-container>
@endsection
