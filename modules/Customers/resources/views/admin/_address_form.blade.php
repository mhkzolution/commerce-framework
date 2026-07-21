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
    <div>
        <label class="block text-sm font-medium text-text" for="city">City</label>
        <input id="city" name="city" value="{{ old('city') }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="state">State</label>
        <input id="state" name="state" value="{{ old('state') }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="postal_code">Postal code</label>
        <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="country_code">Country</label>
        <input id="country_code" name="country_code" value="{{ old('country_code', 'US') }}" maxlength="2" required class="cf-input mt-1 uppercase">
    </div>
    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_default" value="1" class="rounded border-border">
            Set as default for this type
        </label>
    </div>
</div>
