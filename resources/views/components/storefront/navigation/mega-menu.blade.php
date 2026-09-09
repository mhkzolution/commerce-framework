@props([
    'item' => [],
])

@php
    $columns = $item['columns'] ?? [];
@endphp

@if (count($columns) > 0)
    <div
        class="storefront-mega-menu"
        data-mega-menu-panel="{{ $item['id'] ?? '' }}"
        hidden
    >
        <div class="storefront-mega-menu__inner">
            @foreach ($columns as $column)
                <div class="storefront-mega-menu__column">
                    @if (! empty($column['title']))
                        <p class="storefront-mega-menu__title">{{ $column['title'] }}</p>
                    @endif

                    @if (! empty($column['groups']))
                        <div class="storefront-mega-menu__groups">
                            @foreach ($column['groups'] as $group)
                                <div class="storefront-mega-menu__group">
                                    @if (! empty($group['url']))
                                        <a
                                            href="{{ $group['url'] }}"
                                            class="storefront-mega-menu__heading {{ ($group['active'] ?? false) ? 'storefront-mega-menu__heading--active' : '' }}"
                                        >
                                            {{ $group['label'] }}
                                        </a>
                                    @else
                                        <p class="storefront-mega-menu__heading">{{ $group['label'] }}</p>
                                    @endif

                                    @if (($group['children'] ?? []) !== [])
                                        <ul class="storefront-mega-menu__links">
                                            @foreach ($group['children'] as $link)
                                                <li>
                                                    <a
                                                        href="{{ $link['url'] }}"
                                                        class="storefront-mega-menu__link {{ ($link['active'] ?? false) ? 'storefront-mega-menu__link--active' : '' }}"
                                                    >
                                                        {{ $link['label'] }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                    <ul class="storefront-mega-menu__links">
                        @foreach ($column['links'] ?? [] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="storefront-mega-menu__link {{ ($link['active'] ?? false) ? 'storefront-mega-menu__link--active' : '' }}"
                                >
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @endif

                    @if (! empty($column['view_all']))
                        <a href="{{ $column['view_all']['url'] }}" class="storefront-mega-menu__view-all">
                            {{ $column['view_all']['label'] }} →
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
