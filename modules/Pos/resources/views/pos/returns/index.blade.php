@extends('pos::layouts.pos')

@section('title', 'คืนสินค้า')

@section('content')
    <div class="pos-layout">
        <x-pos::nav-rail active="returns" />

        <div class="pos-main">
            <header class="pos-topbar">
                <div>
                    <h1 class="pos-topbar__title">คืนสินค้า / คืนเงิน</h1>
                    <p class="pos-topbar__meta">ค้นหาออเดอร์หน้าร้านเพื่อคืนเงิน</p>
                </div>
                <a href="{{ route('pos.index') }}" class="pos-btn pos-btn--secondary">กลับไปขาย</a>
            </header>

            <div class="pos-page">
                @session('status')
                    <div class="pos-page__alert pos-page__alert--success">{{ $value }}</div>
                @endsession

                @if ($errors->has('refund'))
                    <div class="pos-page__alert pos-page__alert--error">{{ $errors->first('refund') }}</div>
                @endif

                <form method="GET" action="{{ route('pos.returns.index') }}" class="pos-page__toolbar">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="pos-input pos-page__search"
                        placeholder="กรอกเลขออเดอร์ เช่น ORD-..."
                        autofocus
                    >
                    <button type="submit" class="pos-btn pos-btn--primary">ค้นหา</button>
                </form>

                @if ($error)
                    <div class="pos-page__panel">
                        <div class="pos-page__empty">
                            <p class="pos-page__empty-title">{{ $error }}</p>
                            <p class="pos-page__empty-text">ตรวจสอบเลขออเดอร์แล้วลองใหม่</p>
                        </div>
                    </div>
                @elseif ($order)
                    <div class="pos-page__grid">
                        <section class="pos-page__panel">
                            <h2 class="pos-page__panel-title">รายละเอียดออเดอร์</h2>
                            <dl class="pos-detail-list">
                                <div class="pos-detail-list__row">
                                    <dt>เลขออเดอร์</dt>
                                    <dd>{{ $order->order_number }}</dd>
                                </div>
                                <div class="pos-detail-list__row">
                                    <dt>ลูกค้า</dt>
                                    <dd>{{ $order->customer_name ?: 'ลูกค้าทั่วไป' }}</dd>
                                </div>
                                <div class="pos-detail-list__row">
                                    <dt>สถานะ</dt>
                                    <dd>
                                        <span class="pos-status-badge pos-status-badge--{{ $order->status }}">
                                            {{ $statuses[$order->status] ?? $order->status }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="pos-detail-list__row">
                                    <dt>ยอดรวม</dt>
                                    <dd class="pos-detail-list__amount">
                                        {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
                                    </dd>
                                </div>
                                <div class="pos-detail-list__row">
                                    <dt>เวลา</dt>
                                    <dd>{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
                                </div>
                            </dl>

                            <ul class="pos-return-lines">
                                @foreach ($order->lineItems as $line)
                                    <li class="pos-return-lines__item">
                                        <span class="pos-return-lines__name">{{ $line->name }} × {{ $line->quantity }}</span>
                                        <span class="pos-return-lines__amount">{{ number_format($line->line_total / 100, 2) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>

                        <section class="pos-page__panel">
                            <h2 class="pos-page__panel-title">การชำระเงิน</h2>

                            @if ($payment)
                                <dl class="pos-detail-list">
                                    <div class="pos-detail-list__row">
                                        <dt>วิธีชำระ</dt>
                                        <dd>{{ strtoupper($payment->method) }}</dd>
                                    </div>
                                    <div class="pos-detail-list__row">
                                        <dt>สถานะ</dt>
                                        <dd>{{ $paymentStatuses[$payment->status] ?? $payment->status }}</dd>
                                    </div>
                                    <div class="pos-detail-list__row">
                                        <dt>จำนวนเงิน</dt>
                                        <dd>{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</dd>
                                    </div>
                                </dl>

                                @if ($payment->isPaid())
                                    <form
                                        method="POST"
                                        action="{{ route('pos.returns.refund') }}"
                                        class="pos-return-form"
                                        onsubmit="return confirm('ยืนยันคืนเงินออเดอร์นี้?')"
                                    >
                                        @csrf
                                        <input type="hidden" name="order_uuid" value="{{ $order->uuid }}">
                                        <button type="submit" class="pos-btn pos-btn--danger w-full">คืนเงินเต็มจำนวน</button>
                                    </form>
                                @elseif ($payment->isRefunded())
                                    <p class="pos-page__hint">คืนเงินแล้วเมื่อ {{ $payment->refunded_at?->format('d/m/Y H:i') }}</p>
                                @else
                                    <p class="pos-page__hint">ไม่สามารถคืนเงินได้ในสถานะนี้</p>
                                @endif
                            @else
                                <p class="pos-page__hint">ไม่พบข้อมูลการชำระเงินสำหรับออเดอร์นี้</p>
                            @endif

                            <a href="{{ route('pos.receipt.show', $order->uuid) }}" class="pos-btn pos-btn--secondary w-full mt-3" target="_blank" rel="noopener">
                                ดูใบเสร็จ
                            </a>
                        </section>
                    </div>
                @else
                    <div class="pos-page__panel">
                        <div class="pos-page__empty">
                            <p class="pos-page__empty-title">ค้นหาออเดอร์เพื่อคืนเงิน</p>
                            <p class="pos-page__empty-text">กรอกเลขออเดอร์จากใบเสร็จหรือรายการขาย</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
