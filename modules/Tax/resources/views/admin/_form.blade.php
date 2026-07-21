@php $rate = $rate ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="code">Code</label>
        <input id="code" name="code" value="{{ old('code', $rate?->code) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $rate?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="rate_percent">Rate (%)</label>
        <input id="rate_percent" type="number" step="0.01" name="rate_percent" value="{{ old('rate_percent', $rate ? $rate->rate_bps / 100 : '7') }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="country_code">Country</label>
        <input id="country_code" name="country_code" maxlength="2" value="{{ old('country_code', $rate?->country_code) }}" placeholder="US" class="cf-input mt-1 uppercase">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="priority">Priority</label>
        <input id="priority" type="number" name="priority" value="{{ old('priority', $rate?->priority ?? 0) }}" class="cf-input mt-1">
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rate?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
