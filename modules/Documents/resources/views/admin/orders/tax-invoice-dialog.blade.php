@php
    $prefill = old('company_name') !== null
        ? [
            'company_name' => old('company_name'),
            'tax_id' => old('tax_id'),
            'branch_no' => old('branch_no'),
            'billing_address' => old('billing_address', []),
        ]
        : $detail->taxInvoicePrefill;
    $address = is_array($prefill['billing_address'] ?? null) ? $prefill['billing_address'] : [];
@endphp

<dialog id="tax-invoice-dialog" class="cf-dialog w-full max-w-lg overflow-visible rounded-lg border border-border bg-surface p-6 text-text shadow-lg">
    <form method="POST" action="{{ route('admin.orders.tax-invoice.store', $order) }}" class="space-y-4">
        @csrf
        <h2 class="text-lg font-semibold">{{ __('documents::admin.generate_tax_invoice') }}</h2>

        <div>
            <label class="block text-sm font-medium text-text" for="inv-company-name">{{ __('documents::admin.company_name') }}</label>
            <input id="inv-company-name" type="text" name="company_name" value="{{ $prefill['company_name'] ?? '' }}" required class="cf-input mt-1">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-text" for="inv-tax-id">{{ __('documents::admin.tax_id') }}</label>
                <input id="inv-tax-id" type="text" name="tax_id" value="{{ $prefill['tax_id'] ?? '' }}" inputmode="numeric" maxlength="13" required class="cf-input mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-text" for="inv-branch-no">{{ __('documents::admin.branch_no') }}</label>
                <input id="inv-branch-no" type="text" name="branch_no" value="{{ $prefill['branch_no'] ?? '00000' }}" inputmode="numeric" maxlength="5" class="cf-input mt-1">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-text" for="inv-line1">{{ __('documents::admin.line1') }}</label>
            <input id="inv-line1" type="text" name="billing_address[line1]" value="{{ $address['line1'] ?? '' }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="inv-line2">{{ __('documents::admin.line2') }}</label>
            <input id="inv-line2" type="text" name="billing_address[line2]" value="{{ $address['line2'] ?? '' }}" class="cf-input mt-1">
        </div>
        @include('customers::storefront._location_fields', [
            'prefix' => 'billing_address',
            'prefill' => $address,
            'required' => false,
            'stateKey' => 'province',
            'wrapperClass' => 'grid gap-4 sm:grid-cols-2',
            'gridClass' => 'contents',
            'fieldClass' => '',
            'labelClass' => 'block text-sm font-medium text-text',
            'selectClass' => 'cf-input mt-1',
            'inputClass' => 'cf-input mt-1',
        ])

        <div class="flex justify-end gap-3 pt-2">
            <x-admin.button variant="secondary" type="button" onclick="this.closest('dialog').close()">{{ __('documents::admin.cancel') }}</x-admin.button>
            <x-admin.button variant="primary" type="submit">{{ __('documents::admin.issue_tax_invoice') }}</x-admin.button>
        </div>
    </form>
</dialog>
