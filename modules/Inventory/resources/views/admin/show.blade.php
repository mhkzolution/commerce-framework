@extends('layouts.admin')

@section('title', 'Stock')

@section('page')
    @php
        $available = $item->available();
        $isLow = $available > 0 && $available <= $lowStockThreshold;
        $isOut = $available <= 0;
        $availableVariant = $isOut ? 'danger' : ($isLow ? 'warning' : 'published');
    @endphp

    <x-admin.page
        :title="$productName ?? 'Stock item'"
        :description="trim(($variant?->name ?? 'Variant').($variant?->sku ? ' · SKU '.$variant->sku : ''))"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => $variant?->sku ?? 'Stock', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.inventory.index')">Back to inventory</x-admin.button>
        </x-slot:secondaryActions>

        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.stat-card label="On hand" :value="(string) $item->on_hand" />
            <x-admin.stat-card label="Reserved" :value="(string) $item->reserved" />
            <x-admin.stat-card label="Available" :value="(string) $available">
                <x-slot:footer>
                    <x-admin.badge :variant="$availableVariant">
                        {{ $isOut ? 'Out of stock' : ($isLow ? 'Low stock' : 'In stock') }}
                    </x-admin.badge>
                </x-slot:footer>
            </x-admin.stat-card>
        </div>

        @if (app(\Commerce\Contracts\Authorization\AuthorizationServiceInterface::class)->can(auth()->user(), 'inventory.stock.adjust'))
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <x-admin.card title="Adjust stock">
                    <p class="mb-4 text-sm text-muted">Use positive or negative values to change on-hand quantity.</p>
                    <form method="POST" action="{{ route('admin.inventory.adjust', $item) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-text" for="adjust-quantity">Quantity (+/-)</label>
                            <input id="adjust-quantity" name="quantity" type="number" required class="cf-input mt-1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="adjust-reason">Reason</label>
                            <input id="adjust-reason" name="reason" value="{{ old('reason') }}" class="cf-input mt-1">
                        </div>
                        <x-admin.button variant="primary" type="submit">Apply adjustment</x-admin.button>
                    </form>
                </x-admin.card>

                <x-admin.card title="Receive stock">
                    <p class="mb-4 text-sm text-muted">Add incoming inventory with a positive quantity.</p>
                    <form method="POST" action="{{ route('admin.inventory.receive', $item) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-text" for="receive-quantity">Quantity</label>
                            <input id="receive-quantity" name="quantity" type="number" min="1" required class="cf-input mt-1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="receive-reason">Reason</label>
                            <input id="receive-reason" name="reason" value="{{ old('reason') }}" placeholder="PO #, supplier, etc." class="cf-input mt-1">
                        </div>
                        <x-admin.button variant="secondary" type="submit">Receive stock</x-admin.button>
                    </form>
                </x-admin.card>
            </div>
        @endif

        <div class="mt-6">
            <h2 class="mb-4 text-lg font-semibold text-text">Movement history</h2>
            <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">On hand</th>
                        <th class="px-4 py-3">Reason</th>
                    </tr>
                </x-slot:head>

                @forelse ($item->movements as $movement)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $movementTypes[$movement->type] ?? $movement->type }}</td>
                        <td class="px-4 py-3 {{ $movement->quantity < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </td>
                        <td class="px-4 py-3">{{ $movement->on_hand_before }} → {{ $movement->on_hand_after }}</td>
                        <td class="px-4 py-3 text-muted">{{ $movement->reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No movements yet.</td></tr>
                @endforelse
            </x-admin.table.shell>
        </div>
    </x-admin.page>
@endsection
