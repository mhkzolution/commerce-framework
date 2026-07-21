@php $promotion = $promotion ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="code">Code</label>
        <input id="code" name="code" value="{{ old('code', $promotion?->code) }}" required class="cf-input mt-1 uppercase">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $promotion?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="type">Type</label>
        <select id="type" name="type" class="cf-input mt-1">
            <option value="percentage" @selected(old('type', $promotion?->type) === 'percentage')>Percentage</option>
            <option value="fixed" @selected(old('type', $promotion?->type) === 'fixed')>Fixed amount</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="value">Value</label>
        <input id="value" name="value" type="number" step="0.01" value="{{ old('value', $promotion ? ($promotion->type === 'percentage' ? $promotion->value / 100 : $promotion->value / 100) : '') }}" required class="cf-input mt-1">
        <p class="mt-1 text-xs text-muted">Percent or fixed amount in store currency.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="min_subtotal">Min subtotal</label>
        <input id="min_subtotal" name="min_subtotal" type="number" step="0.01" value="{{ old('min_subtotal', $promotion?->min_subtotal ? $promotion->min_subtotal / 100 : '') }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="max_uses">Max uses</label>
        <input id="max_uses" name="max_uses" type="number" value="{{ old('max_uses', $promotion?->max_uses) }}" class="cf-input mt-1">
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
