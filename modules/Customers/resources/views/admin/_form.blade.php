@php
    $customer = $customer ?? null;
    $passwordRequired = $passwordRequired ?? $customer === null;
    $showAddress = $showAddress ?? $customer === null;
    $addressPrefill = old('address', []);
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="name">{{ __('storefront::storefront.full_name') }}</label>
        <input id="name" name="name" value="{{ old('name', $customer?->name) }}" required autocomplete="name" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="phone">{{ __('storefront::storefront.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $customer?->phone) }}" required autocomplete="tel" inputmode="tel" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="email">{{ __('storefront::storefront.email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email', $customer?->email) }}" required autocomplete="email" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="status">Status</label>
        <select id="status" name="status" class="cf-input mt-1">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $customer?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="password">{{ __('customers::auth.password') }}</label>
        <input
            id="password"
            name="password"
            type="password"
            autocomplete="new-password"
            @required($passwordRequired)
            class="cf-input mt-1"
        >
        <p class="mt-1 text-sm text-muted">
            @if ($passwordRequired)
                {{ __('storefront::storefront.password_hint') }}
            @else
                {{ __('settings::admin.mail_password_hint') }}
            @endif
        </p>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="password_confirmation">{{ __('customers::auth.confirm_password') }}</label>
        <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            autocomplete="new-password"
            @required($passwordRequired)
            class="cf-input mt-1"
        >
    </div>
</div>

@if ($showAddress)
    <div class="space-y-4">
        <div>
            <h4 class="text-sm font-semibold text-text">{{ __('storefront::storefront.delivery_information') }}</h4>
            <p class="mt-1 text-sm text-muted">{{ __('storefront::storefront.addresses_description') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="address_line1">{{ __('storefront::storefront.address_house_street') }}</label>
            <input
                id="address_line1"
                name="address[line1]"
                value="{{ old('address.line1', $addressPrefill['line1'] ?? '') }}"
                autocomplete="address-line1"
                class="cf-input mt-1"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="address_line2">{{ __('storefront::storefront.address_landmark') }}</label>
            <input
                id="address_line2"
                name="address[line2]"
                value="{{ old('address.line2', $addressPrefill['line2'] ?? '') }}"
                autocomplete="address-line2"
                class="cf-input mt-1"
            >
        </div>
        @include('customers::storefront._location_fields', [
            'prefix' => 'address',
            'prefill' => $addressPrefill,
            'required' => false,
            'wrapperClass' => 'grid gap-4 md:grid-cols-2',
            'gridClass' => 'contents',
            'fieldClass' => '',
            'labelClass' => 'block text-sm font-medium text-text',
            'selectClass' => 'cf-input mt-1',
            'inputClass' => 'cf-input mt-1',
        ])
    </div>
@endif
