@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.create_account'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container variant="narrow" class="storefront-shopper storefront-auth">
        <h1 class="storefront-shopper__title">{{ __('storefront::storefront.create_account') }}</h1>

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('storefront.account.register.store') }}" class="storefront-panel storefront-stack">
            @csrf
            <div class="storefront-field">
                <label class="storefront-field__label" for="name">{{ __('storefront::storefront.name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="storefront-input">
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="email">{{ __('storefront::storefront.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="storefront-input">
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="phone">{{ __('storefront::storefront.phone') }}</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="storefront-input">
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="password">{{ __('storefront::storefront.password') }}</label>
                <input id="password" type="password" name="password" required class="storefront-input">
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="password_confirmation">{{ __('storefront::storefront.confirm_password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="storefront-input">
            </div>
            <button type="submit" class="storefront-btn storefront-btn--block">{{ __('storefront::storefront.create_account') }}</button>
        </form>

        <p class="storefront-muted">
            {{ __('storefront::storefront.already_have_account') }}
            <a href="{{ route('storefront.account.login') }}" class="storefront-link">{{ __('storefront::storefront.sign_in') }}</a>
        </p>
    </x-storefront.layout.page-container>
@endsection
