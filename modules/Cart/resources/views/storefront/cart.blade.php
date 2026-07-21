@extends('cart::layouts.storefront')

@section('title', 'Cart')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-text">Cart</h1>
        @if ($cart->lines !== [])
            <form method="POST" action="{{ route('storefront.cart.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-danger hover:underline">Clear cart</button>
            </form>
        @endif
    </div>

    @session('status')
        <div class="cf-flash cf-flash--success mt-4">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">{{ $errors->first() }}</div>
    @endif

    @if ($cart->lines === [])
        <p class="mt-8 text-muted">
            Your cart is empty.
            <a href="{{ route('storefront.shop.index') }}" class="text-link hover:underline">Continue shopping</a>
        </p>
    @else
        <div class="mt-8 overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-surface-muted">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Product</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Price</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Qty</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Total</th>
                        <th class="px-4 py-3 text-right font-medium text-text-secondary">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($cart->lines as $line)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-text">{{ $line->name }}</div>
                                <div class="text-xs text-muted">{{ $line->sku ?? $line->purchasableUuid }}</div>
                                @if ($line->available < $line->quantity)
                                    <div class="mt-1 text-xs text-danger">Only {{ $line->available }} available</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ number_format($line->unitPrice / 100, 2) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('storefront.cart.items.update', $line->purchasableUuid) }}" class="inline-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" class="cf-input w-16 py-1">
                                    <button type="submit" class="text-muted hover:text-text">Update</button>
                                </form>
                            </td>
                            <td class="px-4 py-3">{{ number_format($line->lineTotal / 100, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('storefront.cart.items.destroy', $line->purchasableUuid) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col gap-4 rounded-lg border border-border bg-surface p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-sm text-muted">Subtotal ({{ $cart->itemCount }} items)</div>
                <div class="text-2xl font-semibold text-text">{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</div>
                @if ($cart->discountTotal > 0)
                    <div class="mt-1 text-sm text-success">
                        {{ $cart->promotionName }} ({{ $cart->couponCode }}): -{{ number_format($cart->discountTotal / 100, 2) }}
                    </div>
                @endif
            </div>
            <a href="{{ route('storefront.checkout') }}" class="cf-btn cf-btn--primary text-center">Checkout</a>
        </div>

        <section class="mt-6 rounded-lg border border-border bg-surface p-4 shadow-sm">
            <h2 class="text-sm font-medium text-text">Promotion code</h2>
            @if ($cart->couponCode)
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-success">{{ $cart->couponCode }} applied</span>
                    <form method="POST" action="{{ route('storefront.cart.coupon.remove') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-danger hover:underline">Remove</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('storefront.cart.coupon.apply') }}" class="mt-2 flex gap-2">
                    @csrf
                    <input name="code" placeholder="Enter code" class="cf-input flex-1 uppercase">
                    <button type="submit" class="cf-btn cf-btn--secondary">Apply</button>
                </form>
            @endif
            @error('coupon')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        </section>
    @endif
@endsection
