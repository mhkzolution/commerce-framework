<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="label">Label</label>
        <input id="label" name="label" value="{{ old('label') }}" placeholder="Home" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="type">Type</label>
        <select id="type" name="type" class="cf-input mt-1">
            <option value="both" @selected(old('type', 'both') === 'both')>Shipping & billing</option>
            <option value="shipping" @selected(old('type') === 'shipping')>Shipping</option>
            <option value="billing" @selected(old('type') === 'billing')>Billing</option>
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="line1">Address line 1</label>
        <input id="line1" name="line1" value="{{ old('line1') }}" required class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="line2">Address line 2</label>
        <input id="line2" name="line2" value="{{ old('line2') }}" class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        @include('customers::storefront._location_fields', [
            'prefix' => '',
            'required' => true,
            'wrapperClass' => 'grid gap-4 sm:grid-cols-2',
            'gridClass' => 'contents',
            'fieldClass' => '',
            'labelClass' => 'block text-sm font-medium text-text',
            'selectClass' => 'cf-input mt-1',
            'inputClass' => 'cf-input mt-1',
        ])
    </div>
    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_default" value="1" class="rounded border-border">
            Set as default for this type
        </label>
    </div>
</div>
