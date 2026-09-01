<p {{ $attributes->merge(['class' => 'storefront-auth-divider']) }}>
    <span>{{ $slot->isEmpty() ? __('customers::auth.or_continue_with') : $slot }}</span>
</p>
