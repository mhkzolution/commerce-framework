@php
    $homePopups = $homePopups ?? [];
    $first = $homePopups[0] ?? null;
    $showDelay = is_array($first) ? (int) ($first['showDelay'] ?? 0) : 0;
    $autoClose = is_array($first) ? (int) ($first['autoClose'] ?? 0) : 0;
    $canClose = collect($homePopups)->contains(static fn (array $popup): bool => (bool) ($popup['closable'] ?? true));
@endphp

@if ($homePopups !== [])
    <div
        class="storefront-home-popup"
        data-home-popup
        hidden
        data-show-delay="{{ $showDelay }}"
        data-auto-close="{{ $autoClose }}"
        data-storage-key="commerce:home-popup"
    >
        <div class="storefront-home-popup__scrim" data-home-popup-scrim @if ($canClose) tabindex="-1" @endif></div>
        <div
            class="storefront-home-popup__dialog"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('storefront::storefront.home') }}"
        >
            <div class="storefront-home-popup__frame" data-home-popup-frame>
                <div class="storefront-home-popup__track" data-home-popup-track>
                    @foreach ($homePopups as $index => $popup)
                        @php
                            $type = $popup['type'] ?? 'image';
                            $hasCopy = filled($popup['headline'] ?? null) || filled($popup['subheadline'] ?? null) || filled($popup['buttonText'] ?? null);
                            $isImageOnly = $type === 'image' || ! $hasCopy;
                        @endphp
                        <article
                            class="storefront-home-popup__slide is-type-{{ $type }}{{ $isImageOnly ? ' is-image-only' : '' }}"
                            data-home-popup-slide
                            data-popup-slug="{{ $popup['slug'] }}"
                            data-closable="{{ ! empty($popup['closable']) ? '1' : '0' }}"
                        >
                            @if (filled($popup['imageUrl'] ?? null))
                                @if ($isImageOnly && filled($popup['buttonUrl'] ?? null))
                                    <a
                                        class="storefront-home-popup__media"
                                        href="{{ $popup['buttonUrl'] }}"
                                        @if (($popup['buttonTarget'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        <img src="{{ $popup['imageUrl'] }}" srcset="{{ $popup['imageSrcset'] ?? '' }}" alt="{{ $popup['headline'] ?? $popup['slug'] }}" sizes="(max-width: 40rem) 90vw, 32rem">
                                    </a>
                                @else
                                    <div class="storefront-home-popup__media">
                                        <img src="{{ $popup['imageUrl'] }}" srcset="{{ $popup['imageSrcset'] ?? '' }}" alt="{{ $popup['headline'] ?? $popup['slug'] }}" sizes="(max-width: 40rem) 90vw, 32rem">
                                    </div>
                                @endif
                            @endif
                            @unless ($isImageOnly)
                                <div class="storefront-home-popup__copy">
                                    @if (filled($popup['headline'] ?? null))
                                        <h2 class="storefront-home-popup__headline">{{ $popup['headline'] }}</h2>
                                    @endif
                                    @if (filled($popup['subheadline'] ?? null))
                                        <p class="storefront-home-popup__subheadline">{{ $popup['subheadline'] }}</p>
                                    @endif
                                    @if (filled($popup['buttonText'] ?? null) && filled($popup['buttonUrl'] ?? null))
                                        <a
                                            class="storefront-home-popup__cta"
                                            href="{{ $popup['buttonUrl'] }}"
                                            @if (($popup['buttonTarget'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif
                                        >{{ $popup['buttonText'] }}</a>
                                    @endif
                                </div>
                            @endunless
                        </article>
                    @endforeach
                </div>
            </div>

            @if (count($homePopups) > 1)
                <button type="button" class="storefront-home-popup__nav storefront-home-popup__nav--prev" data-home-popup-prev aria-label="{{ __('storefront::storefront.popup_previous') }}">‹</button>
                <button type="button" class="storefront-home-popup__nav storefront-home-popup__nav--next" data-home-popup-next aria-label="{{ __('storefront::storefront.popup_next') }}">›</button>
                <div class="storefront-home-popup__dots" data-home-popup-dots></div>
            @endif

            @if ($canClose)
                <button type="button" class="storefront-home-popup__close" data-home-popup-close aria-label="{{ __('storefront::storefront.close') }}">×</button>
                <label class="storefront-home-popup__optout">
                    <input type="checkbox" data-home-popup-optout>
                    <span>{{ __('storefront::storefront.popup_hide_seven_days') }}</span>
                </label>
            @endif
        </div>
    </div>
@endif
