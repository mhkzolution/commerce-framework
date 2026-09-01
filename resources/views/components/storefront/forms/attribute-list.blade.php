@props([
    'items' => [],
])

@if ($items !== [])
    <dl {{ $attributes->merge(['class' => 'storefront-attribute-list']) }}>
        @foreach ($items as $attribute)
            <div class="storefront-attribute-list__row">
                <dt class="storefront-attribute-list__label">{{ $attribute['label'] }}</dt>
                <dd class="storefront-attribute-list__value">{{ $attribute['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
