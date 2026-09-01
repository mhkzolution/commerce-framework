@props([
    'customer',
    'active' => 'dashboard',
    'title',
    'description' => null,
])

<div class="storefront-account">
    <div class="storefront-account__layout">
        <x-storefront.account.sidebar :customer="$customer" :active="$active" />

        <section class="storefront-account-content">
            <header>
                <h1 class="storefront-account-content__title">{{ $title }}</h1>
                @if ($description)
                    <p class="storefront-account-content__description">{{ $description }}</p>
                @endif
            </header>

            <div class="mt-6">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
