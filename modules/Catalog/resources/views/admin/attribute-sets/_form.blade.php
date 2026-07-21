<div>
    <label class="block text-sm font-medium text-text" for="code">Code</label>
    <input id="code" name="code" value="{{ old('code', $attributeSet?->code) }}" required class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $attributeSet?->name) }}" required class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text">Attributes</label>
    <div class="mt-2 max-h-64 space-y-2 overflow-y-auto rounded-md border border-border p-3">
        @forelse ($attributes as $attribute)
            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input
                    type="checkbox"
                    name="attribute_ids[]"
                    value="{{ $attribute->id }}"
                    @checked(in_array($attribute->id, old('attribute_ids', $attributeSet?->attributes->pluck('id')->all() ?? []), true))
                    class="rounded border-border"
                >
                <span>{{ $attribute->name }} <span class="text-muted">({{ $attribute->code }})</span></span>
            </label>
        @empty
            <p class="text-sm text-muted">Create attributes first.</p>
        @endforelse
    </div>
</div>
