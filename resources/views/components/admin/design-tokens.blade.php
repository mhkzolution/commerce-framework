@php
    $overrides = config('design.overrides', []);
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
