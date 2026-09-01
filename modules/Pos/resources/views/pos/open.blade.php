@extends('pos::layouts.pos')

@section('title', 'เปิดกะ')

@section('content')
    <div class="pos-layout">
        <x-pos::nav-rail active="pos" />

        <div class="pos-main flex items-center justify-center p-8">
            <div class="pos-session-card">
                <h1 class="text-2xl font-bold text-text">{{ $register->name }}</h1>
                <p class="mt-1 text-sm text-muted">{{ $register->code }}@if($register->location) · {{ $register->location }}@endif</p>

                @session('status')
                    <div class="mt-4 rounded-md border border-border bg-background px-3 py-2 text-sm text-text">{{ $value }}</div>
                @endsession

                <p class="mt-6 text-sm text-muted">เปิดกะก่อนเริ่มขายหน้าร้าน</p>

                <form method="POST" action="{{ route('pos.session.open') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="opening_balance" class="block text-sm font-semibold text-text">เงินทอนเริ่มต้น (บาท)</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-muted">฿</span>
                            <input
                                type="number"
                                name="opening_balance"
                                id="opening_balance"
                                min="0"
                                step="0.01"
                                value="{{ old('opening_balance', '0.00') }}"
                                class="pos-input pl-8"
                                autofocus
                            >
                        </div>
                        <p class="mt-1 text-xs text-muted">กรอกเป็นบาท เช่น 1000.00</p>
                    </div>
                    <button type="submit" class="pos-btn pos-btn--primary w-full">เปิดกะ</button>
                </form>
            </div>
        </div>
    </div>
@endsection
