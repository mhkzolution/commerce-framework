@php
    $option ??= null;
    $suggestedCode ??= '';
    $optionValues = old('options', $option?->options ?? []);
@endphp

<div>
    <label class="block text-sm font-medium text-text" for="name">{{ __('product::workspace.variant_option_name') }}</label>
    <input id="name" name="name" value="{{ old('name', $option?->name) }}" required class="cf-input mt-1" placeholder="เช่น สี, ไซส์">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="code">{{ __('product::workspace.variant_option_code') }}</label>
    <input id="code" name="code" value="{{ old('code', $option?->code ?? ($suggestedCode ?? '')) }}" required class="cf-input mt-1 font-mono text-sm">
    <p class="mt-1 text-xs text-muted">{{ __('product::workspace.variant_option_code_hint') }}</p>
</div>
<div>
    <label class="block text-sm font-medium text-text">{{ __('product::workspace.variant_option_values') }}</label>
    <p class="mt-1 text-xs text-muted">{{ __('product::workspace.variant_option_values_hint') }}</p>

    <div class="mt-3 space-y-2" data-variant-option-values-list>
        @forelse ($optionValues as $value)
            <div class="flex items-center gap-2" data-variant-option-value-row>
                <input type="text" name="options[]" value="{{ $value }}" class="cf-input min-w-0 flex-1" required>
                <button type="button" class="cf-btn cf-btn-secondary shrink-0 px-3" data-remove-variant-option-value>
                    {{ __('product::workspace.remove') }}
                </button>
            </div>
        @empty
            <p class="text-sm text-muted" data-variant-option-values-empty>{{ __('product::workspace.variant_option_values_empty') }}</p>
        @endforelse
    </div>

    <button type="button" class="cf-btn cf-btn-secondary mt-3" data-add-variant-option-value>
        {{ __('product::workspace.variant_option_add_value') }}
    </button>
</div>
<div>
    <label class="block text-sm font-medium text-text" for="position">{{ __('product::workspace.variant_option_position') }}</label>
    <input id="position" type="number" min="0" name="position" value="{{ old('position', $option?->position ?? 0) }}" class="cf-input mt-1">
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const list = document.querySelector('[data-variant-option-values-list]');
                const addButton = document.querySelector('[data-add-variant-option-value]');

                if (!list || !addButton) {
                    return;
                }

                const syncEmptyState = () => {
                    const empty = list.querySelector('[data-variant-option-values-empty]');
                    const hasRows = list.querySelector('[data-variant-option-value-row]') !== null;
                    empty?.classList.toggle('hidden', hasRows);
                };

                const addRow = (value = '') => {
                    list.querySelector('[data-variant-option-values-empty]')?.classList.add('hidden');

                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2';
                    row.dataset.variantOptionValueRow = '1';
                    row.innerHTML = `
                        <input type="text" name="options[]" value="${String(value).replace(/"/g, '&quot;')}" class="cf-input min-w-0 flex-1" required>
                        <button type="button" class="cf-btn cf-btn-secondary shrink-0 px-3" data-remove-variant-option-value>{{ __('product::workspace.remove') }}</button>
                    `;
                    list.appendChild(row);
                    row.querySelector('input')?.focus();
                };

                addButton.addEventListener('click', () => addRow());

                list.addEventListener('click', (event) => {
                    if (!event.target.matches('[data-remove-variant-option-value]')) {
                        return;
                    }

                    event.target.closest('[data-variant-option-value-row]')?.remove();
                    syncEmptyState();
                });

                if (!list.querySelector('[data-variant-option-value-row]')) {
                    addRow();
                }

                syncEmptyState();
            });
        </script>
    @endpush
@endonce
