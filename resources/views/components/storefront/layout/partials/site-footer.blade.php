@props([
    'footer' => null,
])

@php
    use Commerce\Settings\Footer\DTO\FooterBrandData;
    use Commerce\Settings\Footer\DTO\FooterLinkData;
    use Commerce\Settings\Footer\DTO\FooterPageData;
    use Commerce\Settings\Footer\DTO\FooterSectionData;

    $footer = $footer instanceof FooterPageData ? $footer : null;
@endphp

@if ($footer !== null && $footer->enabled && $footer->sections !== [])
    <footer class="storefront-site-footer {{ $footer->className }}">
        <div class="storefront-site-footer__inner">
            @foreach ($footer->sections as $section)
                @continue(! $section instanceof FooterSectionData)

                @switch($section->type)
                    @case('brand')
                        @if ($section->brand instanceof FooterBrandData)
                            <section class="storefront-site-footer__section storefront-site-footer__section--brand" aria-label="{{ $section->ariaLabel }}">
                                @if ($section->brand->logoUrl)
                                    <img
                                        src="{{ $section->brand->logoUrl }}"
                                        alt="{{ $section->brand->displayName ?? 'Store logo' }}"
                                        class="storefront-site-footer__logo"
                                        loading="lazy"
                                    >
                                @endif

                                @if ($section->brand->displayName)
                                    <p class="storefront-site-footer__brand-name">{{ $section->brand->displayName }}</p>
                                @endif

                                @if ($section->brand->description)
                                    <p class="storefront-site-footer__description">{{ $section->brand->description }}</p>
                                @endif
                            </section>
                        @endif
                        @break

                    @case('social')
                        @if ($section->links !== [])
                            <section class="storefront-site-footer__section storefront-site-footer__section--social">
                                @if ($section->title)
                                    <h2 class="storefront-site-footer__heading">{{ $section->title }}</h2>
                                @endif

                                <nav class="storefront-site-footer__social" aria-label="{{ $section->ariaLabel }}">
                                    <ul class="storefront-site-footer__social-list" role="list">
                                        @foreach ($section->links as $link)
                                            @continue(! $link instanceof FooterLinkData)
                                            <li>
                                                <a
                                                    href="{{ $link->url }}"
                                                    @class([
                                                        'storefront-site-footer__social-link',
                                                        'storefront-site-footer__social-link--'.$link->key => $link->key,
                                                    ])
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    aria-label="{{ $link->label }}"
                                                >
                                                    {{ $link->label }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </nav>
                            </section>
                        @endif
                        @break

                    @case('navigation')
                    @case('cms')
                    @case('marketplace')
                        @if ($section->links !== [])
                            <nav class="storefront-site-footer__section storefront-site-footer__section--links" aria-label="{{ $section->ariaLabel }}">
                                @if ($section->title)
                                    <h2 class="storefront-site-footer__heading">{{ $section->title }}</h2>
                                @endif

                                <ul class="storefront-site-footer__list" role="list">
                                    @foreach ($section->links as $link)
                                        @continue(! $link instanceof FooterLinkData)
                                        <li>
                                            <a href="{{ $link->url }}" class="storefront-site-footer__link">{{ $link->label }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </nav>
                        @endif
                        @break

                    @case('copyright')
                        @if ($section->text)
                            <section class="storefront-site-footer__section storefront-site-footer__section--meta">
                                <p class="storefront-site-footer__meta-text">{{ $section->text }}</p>
                            </section>
                        @endif
                        @break

                    @case('powered_by')
                        @if ($section->text)
                            <section class="storefront-site-footer__section storefront-site-footer__section--meta">
                                <p class="storefront-site-footer__meta-text storefront-site-footer__meta-text--muted">{{ $section->text }}</p>
                            </section>
                        @endif
                        @break
                @endswitch
            @endforeach
        </div>
    </footer>
@endif
