@page { size: A4 portrait; margin: 12mm; }
@font-face {
    font-family: '{{ $view->fontFamily() }}';
    font-style: normal;
    font-weight: 400;
    src: url('{{ $view->fontUri() }}') format('truetype');
}
@font-face {
    font-family: '{{ $view->fontFamily() }}';
    font-style: normal;
    font-weight: 700;
    src: url('{{ $view->fontBoldUri() }}') format('truetype');
}
.document-sheet, .document-sheet * { box-sizing: border-box; }
.document-sheet {
    width: 186mm;
    margin: 0 auto;
    color: #111;
    font-family: '{{ $view->fontFamily() }}', DejaVu Sans, sans-serif;
    font-size: 12px;
    line-height: 1.45;
    background: #fff;
}
.document-sheet .header { display: table; width: 100%; margin-bottom: 16px; }
.document-sheet .header-left,
.document-sheet .header-right { display: table-cell; vertical-align: top; }
.document-sheet .header-right { text-align: right; }
.document-sheet .title-th { font-size: 20px; font-weight: 700; margin: 0; }
.document-sheet .title-en { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; margin: 2px 0 0; color: #333; }
.document-sheet .doc-number { font-size: 14px; font-weight: 700; margin: 0; }
.document-sheet .muted { color: #555; }
.document-sheet .parties { display: table; width: 100%; margin: 12px 0 16px; border-collapse: collapse; }
.document-sheet .party { display: table-cell; width: 50%; vertical-align: top; border: 1px solid #222; padding: 10px 12px; }
.document-sheet .party h2 { margin: 0 0 8px; font-size: 11px; font-weight: 700; }
.document-sheet .party .name { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
.document-sheet table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
.document-sheet table.items th,
.document-sheet table.items td { border: 1px solid #222; padding: 6px 8px; }
.document-sheet table.items th { font-size: 10px; text-align: left; background: #f4f4f4; font-weight: 700; }
.document-sheet .num { text-align: right; white-space: nowrap; }
.document-sheet .totals { width: 46%; margin-left: auto; border-collapse: collapse; }
.document-sheet .totals td { padding: 5px 8px; }
.document-sheet .totals .grand { font-weight: 700; border-top: 2px solid #111; }
