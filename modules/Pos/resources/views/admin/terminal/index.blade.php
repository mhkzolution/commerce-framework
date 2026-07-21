@extends('layouts.admin')

@section('title', 'POS — ' . $register->name)

@section('page')
    <x-admin.page :title="$register->name" :description="'Terminal ' . $register->code . ($register->location ? ' · ' . $register->location : '')">
        <x-slot:primaryActions>
            <form method="POST" action="{{ route('admin.pos.terminal.close', $register) }}" onsubmit="return confirm('Close this session?')">
                @csrf
                <x-admin.button variant="secondary" type="submit">Close session</x-admin.button>
            </form>
        </x-slot:primaryActions>

        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        @if ($errors->has('terminal'))
            <div class="cf-flash cf-flash--danger mb-4">{{ $errors->first('terminal') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            <section class="lg:col-span-3 rounded-lg border border-border bg-surface p-4 shadow-sm">
                <h2 class="text-lg font-medium text-text">Add product</h2>
                <form method="POST" action="{{ route('admin.pos.terminal.items.store', $register) }}" class="mt-4 flex flex-wrap gap-2">
                    @csrf
                    <input name="sku" class="cf-input min-w-[12rem] flex-1" placeholder="Scan or enter SKU" autofocus>
                    <input name="quantity" type="number" min="1" value="1" class="cf-input w-20">
                    <x-admin.button variant="primary" type="submit">Add</x-admin.button>
                </form>

                <div class="mt-6" id="pos-search" data-url="{{ route('admin.pos.terminal.search', $register) }}">
                    <input type="search" id="pos-search-input" class="cf-input" placeholder="Search products by name or SKU...">
                    <div id="pos-search-results" class="mt-3 space-y-2"></div>
                </div>
            </section>

            <section class="lg:col-span-2 rounded-lg border border-border bg-surface p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-text">Current sale</h2>
                    <span class="text-sm text-muted">Session {{ $session->uuid }}</span>
                </div>

                @if ($cart->lines === [])
                    <p class="mt-6 text-sm text-muted">No items yet. Scan a SKU or search products.</p>
                @else
                    <ul class="mt-4 divide-y divide-border">
                        @foreach ($cart->lines as $line)
                            <li class="py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-text">{{ $line->name }}</p>
                                        <p class="text-xs text-muted">{{ $line->sku }}</p>
                                        <p class="mt-1 text-sm">{{ number_format($line->unitPrice / 100, 2) }} × {{ $line->quantity }}</p>
                                    </div>
                                    <p class="font-medium text-text">{{ number_format($line->lineTotal / 100, 2) }}</p>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.pos.terminal.items.update', [$register, $line->purchasableUuid]) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantity" min="0" value="{{ $line->quantity }}" class="cf-input w-16 py-1 text-sm">
                                        <button type="submit" class="text-xs text-muted hover:underline">Update</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.pos.terminal.items.destroy', [$register, $line->purchasableUuid]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-danger hover:underline">Remove</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 border-t border-border pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Subtotal</span>
                            <span class="font-medium text-text">{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</span>
                        </div>
                        <div class="mt-2 flex justify-between text-lg font-semibold">
                            <span>Total</span>
                            <span>{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.pos.terminal.complete', $register) }}" class="mt-6 space-y-3 border-t border-border pt-4">
                        @csrf
                        <input name="customer_name" class="cf-input" placeholder="Customer name (optional)">
                        <input name="customer_email" type="email" class="cf-input" placeholder="Customer email (optional)">
                        <x-admin.button variant="primary" type="submit" class="w-full">Complete cash sale</x-admin.button>
                    </form>
                @endif
            </section>
        </div>
    </x-admin.page>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const input = document.getElementById('pos-search-input');
                const results = document.getElementById('pos-search-results');
                const url = document.getElementById('pos-search')?.dataset.url;
                if (!input || !results || !url) return;

                let timer = null;
                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(async () => {
                        const q = input.value.trim();
                        if (q === '') {
                            results.innerHTML = '';
                            return;
                        }
                        const response = await fetch(`${url}?q=${encodeURIComponent(q)}`);
                        const data = await response.json();
                        results.innerHTML = (data.results || []).map((item) => `
                            <form method="POST" action="{{ route('admin.pos.terminal.items.store', $register) }}" class="flex items-center justify-between rounded-md border border-border p-3">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="purchasable_uuid" value="${item.uuid}">
                                <div>
                                    <p class="font-medium text-text">${item.name}</p>
                                    <p class="text-xs text-muted">${item.sku || ''}</p>
                                </div>
                                <button type="submit" class="cf-btn cf-btn--secondary text-sm">Add ${(item.price / 100).toFixed(2)}</button>
                            </form>
                        `).join('');
                    }, 250);
                });
            });
        </script>
    @endpush
@endsection
