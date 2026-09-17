<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>{{ $view->number }} · ใบกำกับภาษี / Tax Invoice</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin: 12px auto 16px;
            width: 186mm;
        }
        .toolbar button {
            font: inherit;
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
        }
        @media print {
            .toolbar { display: none !important; }
        }
        @include('documents::print.partials.styles')
    </style>
</head>
<body>
    @if (($mode ?? 'print') === 'print')
        <div class="toolbar no-print">
            <button type="button" onclick="window.print()">พิมพ์ / Print</button>
        </div>
    @endif

    @include('documents::print.partials.sheet')
</body>
</html>
