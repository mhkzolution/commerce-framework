@if (count($attributes) > 0)
    <section class="rounded-lg border border-border bg-primary-subtle/40 p-4">
        <h3 class="text-sm font-medium text-text">Attributes</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach ($attributes as $attribute)
                @php
                    $current = $attributeValues[$attribute->id] ?? null;
                    $rawValue = old("attributes.{$attribute->id}", $current?->value);
                    if ($attribute->type === 'multiselect' && is_string($rawValue)) {
                        $rawValue = json_decode($rawValue, true) ?? [];
                    }
                @endphp
                <div class="{{ in_array($attribute->type, ['textarea', 'multiselect'], true) ? 'md:col-span-2' : '' }}">
                    <label class="block text-sm font-medium text-text" for="attribute-{{ $attribute->id }}">
                        {{ $attribute->name }}
                        @if ($attribute->pivot?->is_required ?? $attribute->is_required)
                            <span class="text-danger">*</span>
                        @endif
                    </label>

                    @if ($attribute->type === 'boolean')
                        <input type="hidden" name="attributes[{{ $attribute->id }}]" value="0">
                        <input id="attribute-{{ $attribute->id }}" type="checkbox" name="attributes[{{ $attribute->id }}]" value="1" @checked(filter_var($rawValue, FILTER_VALIDATE_BOOLEAN)) class="mt-2 rounded border-border">
                    @elseif ($attribute->type === 'textarea')
                        <textarea id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]" rows="3" class="cf-input mt-1">{{ $rawValue }}</textarea>
                    @elseif ($attribute->type === 'select')
                        <select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}]" class="cf-input mt-1">
                            <option value="">— Select —</option>
                            @foreach ($attribute->options ?? [] as $option)
                                <option value="{{ $option }}" @selected((string) $rawValue === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($attribute->type === 'multiselect')
                        <select id="attribute-{{ $attribute->id }}" name="attributes[{{ $attribute->id }}][]" multiple class="cf-input mt-1 h-28">
                            @foreach ($attribute->options ?? [] as $option)
                                <option value="{{ $option }}" @selected(is_array($rawValue) && in_array($option, $rawValue, true))>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($attribute->type === 'number')
                        <input id="attribute-{{ $attribute->id }}" type="number" name="attributes[{{ $attribute->id }}]" value="{{ $rawValue }}" class="cf-input mt-1">
                    @else
                        <input id="attribute-{{ $attribute->id }}" type="text" name="attributes[{{ $attribute->id }}]" value="{{ $rawValue }}" class="cf-input mt-1">
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
