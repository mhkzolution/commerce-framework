<div>
    <label class="block text-sm font-medium text-text" for="code">Code</label>
    <input id="code" name="code" value="{{ old('code', $attribute?->code) }}" required class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $attribute?->name) }}" required class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="type">Type</label>
    <select id="type" name="type" class="cf-input mt-1">
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $attribute?->type ?? 'text') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-medium text-text" for="options">Options (one per line, for select types)</label>
    <textarea id="options" name="options" rows="4" class="cf-input mt-1">{{ old('options', isset($attribute?->options) ? implode("\n", $attribute->options) : '') }}</textarea>
</div>
<div class="flex flex-wrap gap-6">
    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
        <input type="hidden" name="is_filterable" value="0">
        <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable', $attribute?->is_filterable ?? false)) class="rounded border-border">
        Filterable
    </label>
    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
        <input type="hidden" name="is_required" value="0">
        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $attribute?->is_required ?? false)) class="rounded border-border">
        Required
    </label>
    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
        <input type="hidden" name="is_visible" value="0">
        <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $attribute?->is_visible ?? true)) class="rounded border-border">
        Visible
    </label>
</div>
<div>
    <label class="block text-sm font-medium text-text" for="position">Position</label>
    <input id="position" type="number" min="0" name="position" value="{{ old('position', $attribute?->position ?? 0) }}" class="cf-input mt-1">
</div>
