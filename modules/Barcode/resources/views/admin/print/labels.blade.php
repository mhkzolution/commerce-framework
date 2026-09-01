<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('barcode::admin.preview.print') }} — {{ $job->template_name }}</title>
    @php
        $labelStyle = $label_style ?? \Commerce\Barcode\Support\BarcodeLabelStyle::resolve([]);
    @endphp
    <style>
        @page {
            size: {{ $layout['paper']['width'] }}mm {{ $layout['paper']['height'] }}mm;
            margin: 0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', ui-sans-serif, system-ui, sans-serif;
            color: #111;
            background: #f4f6f8;
        }

        .bc-print-actions {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .bc-print-actions button,
        .bc-print-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111;
            cursor: pointer;
        }

        .bc-print-actions .primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .bc-print-sheet {
            width: {{ $layout['paper']['width'] }}mm;
            height: {{ $layout['paper']['height'] }}mm;
            position: relative;
            background: #fff;
            margin: 1.5rem auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            page-break-after: always;
            overflow: hidden;
        }

        .bc-print-sheet:last-child {
            page-break-after: auto;
        }

        .bc-print-label {
            position: absolute;
            display: flex;
            overflow: hidden;
            box-sizing: border-box;
            border: 0.15mm solid #b8b8b8;
            background: #fff;
            padding: {{ $labelStyle['padding_top'] }}mm {{ $labelStyle['padding_right'] }}mm {{ $labelStyle['padding_bottom'] }}mm {{ $labelStyle['padding_left'] }}mm;
            gap: {{ $labelStyle['content_gap'] }}mm;
        }

        .bc-print-label--vertical {
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .bc-print-label--horizontal {
            flex-direction: row;
            align-items: center;
        }

        .bc-print-label__owner {
            flex: 0 0 auto;
            width: 100%;
            font-weight: 600;
            text-align: center;
            line-height: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: {{ $labelStyle['owner_font_size'] }}pt;
        }

        .bc-print-label--horizontal .bc-print-label__owner {
            flex: 0 0 24%;
            white-space: normal;
            text-align: left;
        }

        .bc-print-label__barcode {
            flex: 1 1 auto;
            width: 100%;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bc-print-label__barcode svg {
            display: block;
            width: 100%;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }

        .bc-print-label__sku {
            flex: 0 0 auto;
            width: 100%;
            font-family: DejaVu Sans Mono, ui-monospace, monospace;
            text-align: center;
            letter-spacing: 0.04em;
            line-height: 1;
            font-size: {{ $labelStyle['sku_font_size'] }}pt;
        }

        .bc-print-label--horizontal .bc-print-label__sku {
            flex: 0 0 20%;
            text-align: right;
            word-break: break-all;
        }

        @media print {
            body { background: #fff; }
            .bc-print-actions { display: none; }
            .bc-print-sheet {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="bc-print-actions">
        <button type="button" class="primary" onclick="window.print()">{{ __('barcode::admin.preview.print') }}</button>
        <a href="{{ route('admin.barcode.print.pdf', $job) }}">{{ __('barcode::admin.preview.download_pdf') }}</a>
        <a href="{{ route('admin.barcode.index') }}">{{ __('barcode::admin.nav.print') }}</a>
    </div>

    @php
        $orientationClass = ($label_orientation ?? 'vertical') === 'horizontal'
            ? 'bc-print-label--horizontal'
            : 'bc-print-label--vertical';
    @endphp

    @foreach ($pages as $page)
        <div class="bc-print-sheet">
            @foreach ($page['cells'] as $cell)
                @if ($cell['barcode_svg'])
                    <div
                        class="bc-print-label {{ $orientationClass }}"
                        style="left: {{ $cell['left'] }}mm; top: {{ $cell['top'] }}mm; width: {{ $cell['width'] }}mm; height: {{ $cell['height'] }}mm;"
                    >
                        <div class="bc-print-label__owner">{{ $cell['owner_name'] }}</div>
                        <div class="bc-print-label__barcode">{!! $cell['barcode_svg'] !!}</div>
                        <div class="bc-print-label__sku">{{ $cell['display_text'] }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 300);
        });
    </script>
</body>
</html>
