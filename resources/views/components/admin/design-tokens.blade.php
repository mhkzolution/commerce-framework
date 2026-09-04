@php
    $overrides = \Commerce\Settings\Support\ThemeDesignTokens::resolve();
@endphp

@if (count($overrides))
    <style>
        :root {
            @foreach ($overrides as $token => $value)
                --color-{{ $token }}: {{ $value }};
            @endforeach
        }
    </style>
@endif
