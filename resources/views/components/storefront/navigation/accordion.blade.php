@props([
    'items' => [],
])

@if ($items !== [])
    <div {{ $attributes->class('storefront-accordion') }} data-storefront-accordion>
        @foreach ($items as $index => $item)
            @php
                $panelId = 'faq-panel-'.($item['uuid'] ?? $index);
                $buttonId = 'faq-button-'.($item['uuid'] ?? $index);
            @endphp
            <div class="storefront-accordion__item" data-accordion-item>
                <h3 class="storefront-accordion__heading">
                    <button
                        type="button"
                        class="storefront-accordion__trigger"
                        id="{{ $buttonId }}"
                        aria-expanded="false"
                        aria-controls="{{ $panelId }}"
                        data-accordion-trigger
                    >
                        <span>{{ $item['question'] }}</span>
                        <span class="storefront-accordion__icon" aria-hidden="true"></span>
                    </button>
                </h3>
                <div
                    class="storefront-accordion__panel"
                    id="{{ $panelId }}"
                    role="region"
                    aria-labelledby="{{ $buttonId }}"
                    data-accordion-panel
                    hidden
                >
                    <div class="storefront-accordion__content">
                        {!! nl2br(e($item['answer'])) !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
