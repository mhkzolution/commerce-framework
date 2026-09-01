@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@section('page')
    <x-admin.page title="Create purchase order" description="Add expected incoming stock for variants">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Purchase orders', 'url' => route('admin.inventory.purchase-orders.index')],
                ['label' => 'Create', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card>
            <form method="POST" action="{{ route('admin.inventory.purchase-orders.store') }}" class="grid max-w-2xl gap-4" data-po-form>
                @csrf
                <div>
                    <label class="block text-sm font-medium text-text" for="reference">Reference</label>
                    <input id="reference" name="reference" value="{{ old('reference') }}" required class="cf-input mt-1" placeholder="PO-2026-001">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-text" for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id" class="cf-input mt-1">
                            <option value="">— Select supplier —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="supplier_name">Or supplier name</label>
                        <input id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" class="cf-input mt-1" placeholder="One-off supplier">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="supplier_email">Supplier email</label>
                    <input id="supplier_email" type="email" name="supplier_email" value="{{ old('supplier_email') }}" class="cf-input mt-1" placeholder="supplier@example.com">
                    <p class="mt-1 text-xs text-muted">Used when emailing the PO. Defaults to the selected supplier email.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                    <input type="hidden" name="send_email" value="0">
                    <input type="checkbox" name="send_email" value="1" @checked(old('send_email', $autoEmailSupplier ?? false)) class="rounded border-border">
                    Email purchase order PDF to supplier on create
                </label>
                <div>
                    <label class="block text-sm font-medium text-text" for="currency">Currency</label>
                    <select id="currency" name="currency" class="cf-input mt-1">
                        @foreach ($currencies as $currencyCode)
                            <option value="{{ $currencyCode }}" @selected(old('currency', $defaultCurrency) === $currencyCode)>{{ $currencyCode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="expected_at">Expected date</label>
                    <input id="expected_at" type="date" name="expected_at" value="{{ old('expected_at') }}" class="cf-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="cf-input mt-1">{{ old('notes') }}</textarea>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-text">Lines</h3>
                        <button type="button" class="text-sm text-accent hover:underline" data-po-add-line>Add line</button>
                    </div>
                    <div class="space-y-2" data-po-lines>
                        <div class="grid grid-cols-[1fr_6rem_6rem_auto] gap-2" data-po-line>
                            <input name="lines[0][sku]" placeholder="SKU" required class="cf-input">
                            <input type="number" min="0" step="0.01" name="lines[0][unit_cost]" placeholder="Cost" class="cf-input">
                            <input type="number" min="1" name="lines[0][quantity]" value="1" required class="cf-input">
                            <button type="button" class="text-sm text-muted hover:text-danger" data-po-remove-line>&times;</button>
                        </div>
                    </div>
                </div>

                <div>
                    <x-admin.button variant="primary" type="submit">Create purchase order</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-po-form]');
            const lines = form?.querySelector('[data-po-lines]');
            const addButton = form?.querySelector('[data-po-add-line]');

            if (!form || !lines || !addButton) {
                return;
            }

            let index = lines.querySelectorAll('[data-po-line]').length;

            addButton.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-[1fr_6rem_6rem_auto] gap-2';
                row.dataset.poLine = '';
                row.innerHTML = `
                    <input name="lines[${index}][sku]" placeholder="SKU" required class="cf-input">
                    <input type="number" min="0" step="0.01" name="lines[${index}][unit_cost]" placeholder="Cost" class="cf-input">
                    <input type="number" min="1" name="lines[${index}][quantity]" value="1" required class="cf-input">
                    <button type="button" class="text-sm text-muted hover:text-danger" data-po-remove-line>&times;</button>
                `;
                lines.appendChild(row);
                index += 1;
            });

            lines.addEventListener('click', (event) => {
                if (!event.target.matches('[data-po-remove-line]')) {
                    return;
                }

                const row = event.target.closest('[data-po-line]');

                if (lines.querySelectorAll('[data-po-line]').length > 1) {
                    row?.remove();
                }
            });
        });
    </script>
@endpush
