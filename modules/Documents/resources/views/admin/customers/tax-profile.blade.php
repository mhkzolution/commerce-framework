@php
    $profile = $taxProfile ?? null;
    $address = old('billing_address', is_array($profile?->billing_address) ? $profile->billing_address : []);
@endphp

<x-admin.card :title="__('documents::admin.tax_profile')" class="mt-6 max-w-2xl">
    <p class="mb-6 text-sm text-muted">{{ __('documents::admin.tax_profile_description') }}</p>

    <form method="POST" action="{{ route('admin.customers.tax-profile.update', $customer) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-text" for="tax-company-name">{{ __('documents::admin.company_name') }}</label>
            <input id="tax-company-name" type="text" name="company_name" value="{{ old('company_name', $profile?->company_name) }}" required class="cf-input mt-1">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-text" for="tax-tax-id">{{ __('documents::admin.tax_id') }}</label>
                <input id="tax-tax-id" type="text" name="tax_id" value="{{ old('tax_id', $profile?->tax_id) }}" inputmode="numeric" maxlength="13" required class="cf-input mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-text" for="tax-branch-no">{{ __('documents::admin.branch_no') }}</label>
                <input id="tax-branch-no" type="text" name="branch_no" value="{{ old('branch_no', $profile?->branch_no ?? '00000') }}" inputmode="numeric" maxlength="5" class="cf-input mt-1">
                <p class="mt-1 text-xs text-muted">{{ __('documents::admin.branch_no_hint') }}</p>
            </div>
        </div>

        <p class="pt-2 text-sm font-medium text-text">{{ __('documents::admin.billing_address') }}</p>

        <div>
            <label class="block text-sm font-medium text-text" for="tax-line1">{{ __('documents::admin.line1') }}</label>
            <input id="tax-line1" type="text" name="billing_address[line1]" value="{{ $address['line1'] ?? '' }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="tax-line2">{{ __('documents::admin.line2') }}</label>
            <input id="tax-line2" type="text" name="billing_address[line2]" value="{{ $address['line2'] ?? '' }}" class="cf-input mt-1">
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

        <x-admin.button variant="primary" type="submit">{{ __('documents::admin.save_tax_profile') }}</x-admin.button>
    </form>
</x-admin.card>
