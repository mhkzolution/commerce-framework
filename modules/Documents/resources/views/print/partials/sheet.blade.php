<div class="document-sheet">
    <div class="header">
        <div class="header-left">
            <p class="title-th">ใบกำกับภาษี</p>
            <p class="title-en">Tax Invoice</p>
        </div>
        <div class="header-right">
            <p class="doc-number">{{ $view->number }}</p>
            <p class="muted">วันที่ / Date: {{ $view->issuedAtDisplay() }}</p>
            @if ($view->relatedOrderNumber() !== '')
                <p class="muted">อ้างอิงคำสั่งซื้อ / Order: {{ $view->relatedOrderNumber() }}</p>
            @endif
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h2>ผู้ขาย / Seller</h2>
            <div class="name">{{ $view->sellerName() }}</div>
            <div>เลขประจำตัวผู้เสียภาษี / Tax ID: {{ $view->sellerTaxId() }}</div>
            @if ($view->sellerBranchNo() !== '')
                <div>สาขา / Branch: {{ $view->sellerBranchNo() === '00000' ? 'สำนักงานใหญ่ / Head Office (00000)' : $view->sellerBranchNo() }}</div>
            @endif
            @if ($view->sellerAddress() !== '')
                <div>{{ $view->sellerAddress() }}</div>
            @endif
            @if ($view->sellerPhone() !== '')
                <div>โทร / Tel: {{ $view->sellerPhone() }}</div>
            @endif
            @if ($view->sellerEmail() !== '')
                <div>อีเมล / Email: {{ $view->sellerEmail() }}</div>
            @endif
        </div>
        <div class="party">
            <h2>ผู้ซื้อ / Buyer</h2>
            <div class="name">{{ $view->buyerName() }}</div>
            <div>เลขประจำตัวผู้เสียภาษี / Tax ID: {{ $view->buyerTaxId() }}</div>
            @if ($view->buyerBranchNo() !== '')
                <div>สาขา / Branch: {{ $view->buyerBranchNo() === '00000' ? 'สำนักงานใหญ่ / Head Office (00000)' : $view->buyerBranchNo() }}</div>
            @endif
            @foreach ($view->buyerAddressLines() as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>รายการ / Description</th>
                <th>รหัส / SKU</th>
                <th class="num">จำนวน / Qty</th>
                <th class="num">ราคา / Unit</th>
                <th class="num">รวม / Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($view->lines as $line)
                <tr>
                    <td>{{ $line['name'] ?? '' }}</td>
                    <td>{{ $line['sku'] ?? '—' }}</td>
                    <td class="num">{{ $line['quantity'] ?? 0 }}</td>
                    <td class="num">{{ $view->lineMoney($line['unit_price'] ?? 0) }}</td>
                    <td class="num">{{ $view->lineMoney($line['line_total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">—</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>รวม / Subtotal</td>
            <td class="num">{{ $view->money('subtotal') }} {{ $view->currency() }}</td>
        </tr>
        @if ((int) ($view->totals['discount_total'] ?? 0) > 0)
            <tr>
                <td>ส่วนลด / Discount</td>
                <td class="num">-{{ $view->money('discount_total') }}</td>
            </tr>
        @endif
        <tr>
            <td>ภาษี / Tax</td>
            <td class="num">{{ $view->money('tax_total') }}</td>
        </tr>
        <tr>
            <td>ค่าจัดส่ง / Shipping</td>
            <td class="num">{{ $view->money('shipping_total') }}</td>
        </tr>
        <tr class="grand">
            <td>ยอดรวม / Grand total</td>
            <td class="num">{{ $view->money('grand_total') }} {{ $view->currency() }}</td>
        </tr>
    </table>
</div>
