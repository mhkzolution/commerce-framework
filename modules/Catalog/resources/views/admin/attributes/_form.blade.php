@php
    $optionValues = $optionValues ?? [];
    $selectedType = old('type', $attribute?->type ?? $suggestedType ?? 'text');
    $showOptions = in_array($selectedType, ['select', 'multiselect'], true) || $optionValues !== [];
@endphp

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
    <select id="type" name="type" class="cf-input mt-1" data-attribute-type-select>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div data-attribute-options-panel @class(['hidden' => ! $showOptions])>
    <label class="block text-sm font-medium text-text">Options</label>
    <p class="mt-1 text-xs text-muted">Remove values you do not need. Use Select or Multi-select type for these options on products.</p>

    <div class="mt-3 space-y-2" data-attribute-options-list>
        @forelse ($optionValues as $option)
            <div class="flex items-center gap-2" data-attribute-option-row>
                <input
                    type="text"
                    name="options[]"
                    value="{{ $option }}"
                    class="cf-input min-w-0 flex-1"
                >
                <button type="button" class="cf-btn cf-btn-secondary shrink-0 px-3" data-remove-attribute-option aria-label="Remove option">
                    Remove
                </button>
            </div>
        @empty
            <p class="text-sm text-muted" data-attribute-options-empty>No options yet.</p>
        @endforelse
    </div>

    <button type="button" class="cf-btn cf-btn-secondary mt-3" data-add-attribute-option>
        Add option
    </button>
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

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const typeSelect = document.querySelector('[data-attribute-type-select]');
                const panel = document.querySelector('[data-attribute-options-panel]');
                const list = document.querySelector('[data-attribute-options-list]');
                const addButton = document.querySelector('[data-add-attribute-option]');

                if (!typeSelect || !panel || !list || !addButton) {
                    return;
                }

                const syncEmptyState = () => {
                    const empty = list.querySelector('[data-attribute-options-empty]');
                    const hasRows = list.querySelector('[data-attribute-option-row]') !== null;

                    if (empty) {
                        empty.classList.toggle('hidden', hasRows);
                    }
                };

                const togglePanel = () => {
                    const type = typeSelect.value;
                    const hasRows = list.querySelector('[data-attribute-option-row]') !== null;
                    panel.classList.toggle('hidden', !['select', 'multiselect'].includes(type) && !hasRows);
                };

                const addRow = (value = '') => {
                    const empty = list.querySelector('[data-attribute-options-empty]');
                    empty?.classList.add('hidden');

                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2';
                    row.dataset.attributeOptionRow = '1';
                    row.innerHTML = `
                        <input type="text" name="options[]" value="${value.replace(/"/g, '&quot;')}" class="cf-input min-w-0 flex-1">
                        <button type="button" class="cf-btn cf-btn-secondary shrink-0 px-3" data-remove-attribute-option aria-label="Remove option">Remove</button>
                    `;
                    list.appendChild(row);
                    togglePanel();
                    row.querySelector('input')?.focus();
                };

                typeSelect.addEventListener('change', togglePanel);

                addButton.addEventListener('click', () => addRow());

                list.addEventListener('click', (event) => {
                    if (!event.target.matches('[data-remove-attribute-option]')) {
                        return;
                    }

                    event.target.closest('[data-attribute-option-row]')?.remove();
                    syncEmptyState();
                    togglePanel();
                });

                togglePanel();
                syncEmptyState();
            });
        </script>
    @endpush
@endonce
