<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #111; margin: 2rem; }
        h1 { margin: 0 0 0.25rem; font-size: 1.5rem; }
        .meta { color: #555; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: left; }
        th { font-size: 0.75rem; text-transform: uppercase; color: #666; }
        .actions { margin-bottom: 1rem; display: flex; gap: 0.5rem; }
        .summary { display: flex; gap: 2rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">พิมพ์</button>
    </div>

    <h1>{{ $title }}</h1>
    <p class="meta">
        ช่วงเวลา: {{ $filter->range->from->format('d/m/Y') }} – {{ $filter->range->to->format('d/m/Y') }} ·
        ช่องทาง: {{ $filter->channelLabel() }}
    </p>

    @yield('content')
</body>
</html>
