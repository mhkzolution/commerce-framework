@props([
    'viewModel' => null,
])

@php
    $viewModel = is_array($viewModel) ? $viewModel : null;
    $layout = is_array($viewModel['layout'] ?? null) ? $viewModel['layout'] : [];
    $sections = is_array($viewModel['sections'] ?? null) ? $viewModel['sections'] : [];

    $footerClasses = array_values(array_filter([
        'storefront-site-footer',
        $layout['columns']['grid_class'] ?? null,
        $layout['divider']['class'] ?? null,
        $layout['padding']['class'] ?? null,
        $layout['spacing']['class'] ?? null,
        ...((is_array($layout['theme']['classes'] ?? null) ? $layout['theme']['classes'] : [])),
    ]));

    $translate = static function (?string $key): ?string {
        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        $translated = __($key);

        if (! is_string($translated) || trim($translated) === '' || $translated === $key) {
            return null;
        }

        return trim($translated);
    };

    $fallbackLabel = static function (?string $value, string $default): string {
        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        return \Illuminate\Support\Str::headline(trim($value));
    };

    $sectionTitle = static function (array $section) use ($translate): ?string {
        $titleKey = $section['title_key'] ?? null;

        if (! is_string($titleKey) || trim($titleKey) === '') {
            return null;
        }

        return $translate($titleKey);
    };

    $sectionAriaLabel = static function (array $section) use ($sectionTitle, $translate, $fallbackLabel): string {
        $title = $sectionTitle($section);

        if ($title !== null) {
            return $title;
        }

        $type = is_string($section['type'] ?? null) ? trim($section['type']) : '';

        return match ($type) {
            'brand' => $translate('storefront::storefront.footer_brand') ?? 'Footer brand',
            'social' => $translate('storefront::storefront.social_links') ?? 'Social links',
            'navigation', 'cms' => $translate('storefront::storefront.footer_links') ?? 'Footer links',
            'marketplace' => $translate('storefront::storefront.marketplace_links') ?? 'Marketplace links',
            default => $fallbackLabel($type, 'Footer section'),
        };
    };

    $linkLabel = static function (array $item): ?string {
        $label = $item['label'] ?? null;

        return is_string($label) && trim($label) !== '' ? trim($label) : null;
    };

    $socialLinkAriaLabel = static function (array $item) use ($linkLabel, $fallbackLabel): string {
        return $linkLabel($item)
            ?? $fallbackLabel(is_string($item['key'] ?? null) ? $item['key'] : null, 'Social link');
    };

    $linkUrl = static function (array $item): ?string {
        $url = $item['url'] ?? null;

        return is_string($url) && trim($url) !== '' ? trim($url) : null;
    };
@endphp

@if ($viewModel !== null && $sections !== [])
    <footer class="{{ implode(' ', $footerClasses) }}">
        <div class="storefront-site-footer__inner">
            @foreach ($sections as $section)
                @php
                    $type = is_string($section['type'] ?? null) ? $section['type'] : null;
                    $meta = is_array($section['meta'] ?? null) ? $section['meta'] : [];
                    $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                    $title = $sectionTitle($section);
                @endphp

                @switch($type)
                    @case('brand')
                        @php
                            $logoUrl = is_string($meta['logo_url'] ?? null) && trim($meta['logo_url']) !== '' ? trim($meta['logo_url']) : null;
                            $displayName = is_string($meta['display_name'] ?? null) && trim($meta['display_name']) !== '' ? trim($meta['display_name']) : null;
                            $description = is_string($meta['description'] ?? null) && trim($meta['description']) !== '' ? trim($meta['description']) : null;
                        @endphp

                        @if ($logoUrl || $displayName || $description)
                            <section class="storefront-site-footer__section storefront-site-footer__section--brand" aria-label="{{ $sectionAriaLabel($section) }}">
                                @if ($logoUrl)
                                    <img
                                        src="{{ $logoUrl }}"
                                        alt="{{ $displayName ?? ($translate('storefront::storefront.store_logo') ?? 'Store logo') }}"
                                        class="storefront-site-footer__logo"
                                        loading="lazy"
                                    >
                                @endif

                                @if ($displayName)
                                    <p class="storefront-site-footer__brand-name">{{ $displayName }}</p>
                                @endif

                                @if ($description)
                                    <p class="storefront-site-footer__description">{{ $description }}</p>
                                @endif
                            </section>
                        @endif
                        @break

                    @case('social')
                        @if ($items !== [])
                            <section class="storefront-site-footer__section storefront-site-footer__section--social">
                                @if ($title)
                                    <h2 class="storefront-site-footer__heading">{{ $title }}</h2>
                                @endif

                                <nav class="storefront-site-footer__social" aria-label="{{ $sectionAriaLabel($section) }}">
                                    <ul class="storefront-site-footer__social-list" role="list">
                                        @foreach ($items as $item)
                                            @php
                                                $label = is_array($item) ? $linkLabel($item) : null;
                                                $url = is_array($item) ? $linkUrl($item) : null;
                                                $key = is_array($item) && is_string($item['key'] ?? null) ? trim($item['key']) : null;
                                            @endphp

                                            @if ($label && $url)
                                                <li>
                                                    <a
                                                        href="{{ $url }}"
                                                        @class([
                                                            'storefront-site-footer__social-link',
                                                            'storefront-site-footer__social-link--'.$key => $key,
                                                        ])
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        aria-label="{{ is_array($item) ? $socialLinkAriaLabel($item) : 'Social link' }}"
                                                    >
                                                        {{ $label }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </nav>
                            </section>
                        @endif
                        @break

                    @case('navigation')
                    @case('cms')
                        @if ($items !== [])
                            <nav class="storefront-site-footer__section storefront-site-footer__section--links" aria-label="{{ $sectionAriaLabel($section) }}">
                                @if ($title)
                                    <h2 class="storefront-site-footer__heading">{{ $title }}</h2>
                                @endif

                                <ul class="storefront-site-footer__list" role="list">
                                    @foreach ($items as $item)
                                        @php
                                            $label = is_array($item) ? $linkLabel($item) : null;
                                            $url = is_array($item) ? $linkUrl($item) : null;
                                        @endphp

                                        @if ($label && $url)
                                            <li>
                                                <a href="{{ $url }}" class="storefront-site-footer__link">{{ $label }}</a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </nav>
                        @endif
                        @break

                    @case('copyright')
                        @php
                            $text = is_string($meta['text'] ?? null) && trim($meta['text']) !== '' ? trim($meta['text']) : null;
                        @endphp

                        @if ($text)
                            <section class="storefront-site-footer__section storefront-site-footer__section--meta">
                                <p class="storefront-site-footer__meta-text">{{ $text }}</p>
                            </section>
                        @endif
                        @break

                    @case('powered_by')
                        @php
                            $text = is_string($meta['text'] ?? null) && trim($meta['text']) !== '' ? trim($meta['text']) : null;
                        @endphp

                        @if ($text)
                            <section class="storefront-site-footer__section storefront-site-footer__section--meta">
                                <p class="storefront-site-footer__meta-text storefront-site-footer__meta-text--muted">{{ $text }}</p>
                            </section>
                        @endif
                        @break

                    @case('marketplace')
                        @if ($items !== [])
                            <nav class="storefront-site-footer__section storefront-site-footer__section--links" aria-label="{{ $sectionAriaLabel($section) }}">
                                @if ($title)
                                    <h2 class="storefront-site-footer__heading">{{ $title }}</h2>
                                @endif

                                <ul class="storefront-site-footer__list" role="list">
                                    @foreach ($items as $item)
                                        @php
                                            $label = is_array($item) ? $linkLabel($item) : null;
                                            $url = is_array($item) ? $linkUrl($item) : null;
                                        @endphp

                                        @if ($label && $url)
                                            <li>
                                                <a href="{{ $url }}" class="storefront-site-footer__link">{{ $label }}</a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </nav>
                        @endif
                        @break
                @endswitch
            @endforeach
        </div>
    </footer>
@endif
