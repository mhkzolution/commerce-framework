@php
    use Commerce\Settings\Support\ThemeDesignTokens;

    $overrides = ThemeDesignTokens::resolve();
    $hasPrimary = isset($overrides['primary']);
    $hasAccent = isset($overrides['accent']);
@endphp

@if (count($overrides))
    <style>
        :root {
            @foreach ($overrides as $token => $value)
                --color-{{ $token }}: {{ $value }};
            @endforeach
            @if ($hasPrimary)
                @if (! isset($overrides['primary-hover']))
                    --color-primary-hover: color-mix(in srgb, var(--color-primary) 88%, black);
                @endif
                @if (! isset($overrides['primary-active']))
                    --color-primary-active: color-mix(in srgb, var(--color-primary) 76%, black);
                @endif
                --color-primary-subtle: color-mix(in srgb, var(--color-primary) 12%, white);
                --color-primary-subtle-foreground: var(--color-primary-hover, var(--color-primary));
                --color-ring: var(--color-primary);
                --color-focus: var(--color-primary);
                --color-link: var(--color-primary);
                --color-link-hover: var(--color-primary-hover, var(--color-primary));
                --color-accent-subtle: color-mix(in srgb, var(--color-primary) 12%, white);
                --color-chart-1: var(--color-primary);
                --color-sidebar-active: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface, white));
            @endif
            @if ($hasPrimary && ! $hasAccent)
                --color-accent: var(--color-primary);
            @endif
            @if ($hasAccent && ! isset($overrides['accent-hover']))
                --color-accent-hover: color-mix(in srgb, var(--color-accent) 88%, black);
            @endif
        }

        .storefront {
            --pdp-accent: var(--color-accent, var(--color-primary));
            --pdp-accent-hover: var(--color-accent-hover, var(--color-primary-hover, var(--color-primary)));
        }
    </style>
@endif
