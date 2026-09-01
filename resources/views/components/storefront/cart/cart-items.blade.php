@props([
    'lines',
    'currency',
])

<div {{ $attributes->merge(['class' => 'storefront-cart-items']) }}>
    <div class="storefront-cart-toolbar">
        <label class="storefront-cart-toolbar__select-all storefront-cart-toolbar__select-all--desktop">
            <input type="checkbox" data-cart-select-all checked>
            <span>{{ __('storefront::storefront.select_all') }}</span>
        </label>

        <form
            method="POST"
            action="{{ route('storefront.cart.items.destroy-many') }}"
            class="storefront-cart-toolbar__delete-form"
            data-cart-delete-form
        >
            @csrf
            @method('DELETE')
            <div data-cart-delete-items hidden></div>
            <button type="submit" class="storefront-cart-toolbar__delete" data-cart-delete-selected disabled>
                {{ __('storefront::storefront.delete_selected') }}
            </button>
        </form>
    </div>

    <div class="storefront-cart-items__list">
        @foreach ($lines as $line)
            <x-storefront.cart.cart-line :line="$line" :currency="$currency" />
        @endforeach
    </div>
</div>
