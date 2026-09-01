@props([
    'modes',
    'active' => 'email',
])

@php
    $icons = [
        'email' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
        'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'otp' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'storefront-auth-tabs']) }} role="tablist" aria-label="{{ __('customers::auth.sign_in') }}">
    @foreach ($modes as $mode)
        <button
            type="button"
            class="storefront-auth-tabs__tab @if ($active === $mode) storefront-auth-tabs__tab--active @endif"
            data-auth-mode="{{ $mode }}"
            role="tab"
            aria-selected="{{ $active === $mode ? 'true' : 'false' }}"
        >
            @if (isset($icons[$mode]))
                <span class="storefront-auth-tabs__icon">{!! $icons[$mode] !!}</span>
            @endif
            <span class="storefront-auth-tabs__label">{{ __('customers::auth.mode_' . $mode) }}</span>
        </button>
    @endforeach
</div>
