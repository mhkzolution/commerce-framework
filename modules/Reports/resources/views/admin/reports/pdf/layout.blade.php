<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, ui-sans-serif, system-ui, sans-serif; color: #111; margin: 1.5rem; font-size: 12px; }
        h1 { margin: 0 0 0.25rem; font-size: 1.25rem; }
        .meta { color: #555; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; }
        th, td { border: 1px solid #ddd; padding: 0.4rem 0.5rem; text-align: left; }
        th { background: #f5f5f5; font-size: 10px; text-transform: uppercase; }
        .summary { display: flex; gap: 1.5rem; flex-wrap: wrap; margin: 1rem 0; }
        .summary div { min-width: 120px; }
        .summary strong { display: block; font-size: 14px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        ช่วงเวลา: {{ $filter->range->from->format('d/m/Y') }} – {{ $filter->range->to->format('d/m/Y') }} ·
        ช่องทาง: {{ $filter->channelLabel() }} ·
        พิมพ์เมื่อ: {{ now()->format('d/m/Y H:i') }}
    </p>

    @yield('content')
</body>
</html>
