@extends('pos::layouts.pos')

@section('title', 'ออเดอร์')

@section('content')
    <div class="pos-layout">
        <x-pos::nav-rail active="orders" />

        <div class="pos-main">
            <header class="pos-topbar">
                <div>
                    <h1 class="pos-topbar__title">ออเดอร์หน้าร้าน</h1>
                    <p class="pos-topbar__meta">รายการขายจาก POS</p>
                </div>
                <a href="{{ route('pos.index') }}" class="pos-btn pos-btn--secondary">กลับไปขาย</a>
            </header>

            <div class="pos-page">
                <form method="GET" action="{{ route('pos.orders.index') }}" class="pos-page__toolbar">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="pos-input pos-page__search"
                        placeholder="ค้นหาเลขออเดอร์, ชื่อลูกค้า, อีเมล..."
                        autofocus
                    >
                    <select name="status" class="pos-input pos-page__status">
                        <option value="">ทุกสถานะ</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="pos-btn pos-btn--primary">ค้นหา</button>
                </form>

                <div class="pos-page__panel">
                    @if ($orders->isEmpty())
                        <div class="pos-page__empty">
                            <p class="pos-page__empty-title">ยังไม่มีออเดอร์</p>
                            <p class="pos-page__empty-text">รายการขายจากหน้าร้านจะแสดงที่นี่</p>
                        </div>
                    @else
                        <div class="pos-orders-table-wrap">
                            <table class="pos-orders-table">
                                <thead>
                                    <tr>
                                        <th>ออเดอร์</th>
                                        <th>ลูกค้า</th>
                                        <th>รายการ</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th>เวลา</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
                                        <tr>
                                            <td class="pos-orders-table__number">{{ $order->order_number }}</td>
                                            <td>{{ $order->customer_name ?: 'ลูกค้าทั่วไป' }}</td>
                                            <td>{{ $order->lineItems->sum('quantity') }}</td>
                                            <td class="pos-orders-table__amount">
                                                {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
                                            </td>
                                            <td>
                                                <span class="pos-status-badge pos-status-badge--{{ $order->status }}">
                                                    {{ $statuses[$order->status] ?? $order->status }}
                                                </span>
                                            </td>
                                            <td class="pos-orders-table__time">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="pos-orders-table__actions">
                                                <a href="{{ route('pos.receipt.show', $order->uuid) }}" class="pos-btn pos-btn--secondary" target="_blank" rel="noopener">
                                                    ใบเสร็จ
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($orders->hasPages())
                            <div class="pos-page__pagination">
                                {{ $orders->withQueryString()->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
