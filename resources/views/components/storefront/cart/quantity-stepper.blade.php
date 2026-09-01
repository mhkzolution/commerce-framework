@props([
    'purchasableUuid',
    'quantity',
    'max' => null,
])

<form
    method="POST"
    action="{{ route('storefront.cart.items.update', $purchasableUuid) }}"
    class="storefront-qty-stepper"
    data-qty-form
>
    @csrf
    @method('PATCH')

    <button
        type="button"
        class="storefront-qty-stepper__btn"
        data-qty-decrease
        aria-label="{{ __('storefront::storefront.decrease_quantity') }}"
    >−</button>

    <label class="sr-only" for="qty-{{ $purchasableUuid }}">{{ __('storefront::storefront.quantity') }}</label>
    <input
        id="qty-{{ $purchasableUuid }}"
        type="number"
        name="quantity"
        value="{{ $quantity }}"
        min="1"
        @if ($max) max="{{ $max }}" @endif
        class="storefront-qty-stepper__input"
        data-qty-input
        inputmode="numeric"
    >

    <button
        type="button"
        class="storefront-qty-stepper__btn"
        data-qty-increase
        aria-label="{{ __('storefront::storefront.increase_quantity') }}"
    >+</button>
</form>
