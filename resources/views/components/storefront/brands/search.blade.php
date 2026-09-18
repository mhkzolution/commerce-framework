@props([
    'placeholder' => null,
])

<div class="storefront-brands-search" data-brands-search role="search">
    <label class="storefront-visually-hidden" for="storefront-brands-search">
        {{ __('storefront::storefront.brands_search_label') }}
    </label>
    <input
        id="storefront-brands-search"
        class="storefront-brands-search__input"
        type="search"
        name="q"
        value=""
        placeholder="{{ $placeholder ?? __('storefront::storefront.brands_search_placeholder') }}"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        enterkeyhint="search"
        data-brands-search-input
    >
</div>
