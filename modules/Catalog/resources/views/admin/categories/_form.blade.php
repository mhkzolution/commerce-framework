<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $category?->name) }}" required class="cf-input mt-1">
</div>

<div>
    <label class="block text-sm font-medium text-text" for="slug">Slug</label>
    <input id="slug" name="slug" value="{{ old('slug', $category?->slug) }}" class="cf-input mt-1" placeholder="auto-generated if empty">
</div>

<div>
    <label class="block text-sm font-medium text-text" for="parent_id">Parent</label>
    <select id="parent_id" name="parent_id" class="cf-input mt-1">
        <option value="">— None —</option>
        @foreach ($parents as $parent)
            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category?->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-medium text-text" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $category?->description) }}</textarea>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="position">Position</label>
        <input id="position" type="number" min="0" name="position" value="{{ old('position', $category?->position ?? 0) }}" class="cf-input mt-1">
    </div>
    <div class="flex items-end">
        <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
