@extends('layouts.admin')

@section('title', 'New Order')

@section('page')
    <x-admin.page title="New Order" description="Create a pending order with line items.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Orders', 'url' => route('admin.orders.index')],
                ['label' => 'New order', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.orders.store') }}" method="POST" class="max-w-4xl">
            @csrf

            <x-admin.form.section title="Customer" description="Select an existing customer or enter guest details.">
                @if (count($customers) > 0)
                    <div>
                        <label class="block text-sm font-medium text-text" for="customer_uuid">Customer</label>
                        <select id="customer_uuid" name="customer_uuid" class="cf-input mt-1">
                            <option value="">— Guest / manual entry —</option>
                            @foreach ($customers as $customer)
                                <option
                                    value="{{ $customer->uuid }}"
                                    data-name="{{ $customer->name }}"
                                    data-email="{{ $customer->email }}"
                                    @selected(old('customer_uuid') === $customer->uuid)
                                >{{ $customer->name }} ({{ $customer->email }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-text" for="customer_name">Customer name</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="cf-input mt-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="customer_email">Customer email</label>
                        <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" class="cf-input mt-1">
                    </div>
                </div>
            </x-admin.form.section>

            <x-admin.form.section title="Line items">
                <div class="space-y-4">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="md:col-span-2">
                                <label class="block text-xs text-muted">Variant</label>
                                <select name="lines[{{ $i }}][purchasable_uuid]" class="cf-input mt-1">
                                    <option value="">— Select variant —</option>
                                    @foreach ($variants as $variant)
                                        <option value="{{ $variant->uuid }}" @selected(old("lines.{$i}.purchasable_uuid") === $variant->uuid)>
                                            {{ $variant->product?->name }} — {{ $variant->name ?? $variant->sku ?? $variant->uuid }}
                                            ({{ number_format($variant->price / 100, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-muted">Quantity</label>
                                <input type="number" name="lines[{{ $i }}][quantity]" min="1" value="{{ old("lines.{$i}.quantity", 1) }}" class="cf-input mt-1">
                            </div>
                        </div>
                    @endfor
                </div>
            </x-admin.form.section>

            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.orders.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create order</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection

@push('scripts')
    <script>
        document.getElementById('customer_uuid')?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            if (!option || !option.value) return;
            document.getElementById('customer_name').value = option.dataset.name || '';
            document.getElementById('customer_email').value = option.dataset.email || '';
        });
    </script>
@endpush
