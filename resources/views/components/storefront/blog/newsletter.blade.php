@props([])

<section {{ $attributes->merge(['class' => 'storefront-newsletter']) }}>
    <h2 class="storefront-newsletter__title">{{ __('cms::blog.newsletter_title') }}</h2>
    <p class="storefront-newsletter__description">{{ __('cms::blog.newsletter_description') }}</p>
    <form class="storefront-newsletter__form" data-newsletter-form>
        <label class="sr-only" for="blog-newsletter-email">{{ __('cms::blog.newsletter_email') }}</label>
        <input id="blog-newsletter-email" type="email" name="email" required class="cf-input" placeholder="{{ __('cms::blog.newsletter_email') }}">
        <x-admin.button type="submit" variant="primary">{{ __('cms::blog.newsletter_submit') }}</x-admin.button>
    </form>
    <p class="storefront-newsletter__note" data-newsletter-success hidden>{{ __('cms::blog.newsletter_success') }}</p>
</section>
