@php
    $currency = $currency ?? null;
    $rate = old('rate', $currency ? \Commerce\Currency\Support\CurrencyFormData::rateFromMicro($currency->rate_micro) : '1.000000');
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="code">Code</label>
        <input id="code" name="code" value="{{ old('code', $currency?->code) }}" required maxlength="3" class="cf-input mt-1 uppercase">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="symbol">Symbol</label>
        <input id="symbol" name="symbol" value="{{ old('symbol', $currency?->symbol) }}" required class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $currency?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="rate">Exchange rate</label>
        <input id="rate" type="number" step="0.000001" min="0.000001" name="rate" value="{{ $rate }}" @if ($currency?->is_base) readonly @endif class="cf-input mt-1">
        <p class="mt-1 text-xs text-muted">Units of this currency per 1 base currency. Base currency rate is always 1.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="decimal_places">Decimal places</label>
        <input id="decimal_places" type="number" min="0" max="4" name="decimal_places" value="{{ old('decimal_places', $currency?->decimal_places ?? 2) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="sort_order">Sort order</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $currency?->sort_order ?? 0) }}" class="cf-input mt-1">
    </div>
    <div class="flex flex-col justify-end gap-3">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_base" value="1" @checked(old('is_base', $currency?->is_base ?? false)) class="rounded border-border">
            Base currency
        </label>
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $currency?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
