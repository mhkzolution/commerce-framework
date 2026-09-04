@extends('pos::layouts.pos')

@section('title', 'POS Unavailable')

@section('content')
    <div class="pos-layout">
        <x-pos::nav-rail active="pos" />

        <div class="pos-main flex items-center justify-center p-8">
            <div class="w-full max-w-md text-center">
                <h1 class="text-2xl font-bold text-text">POS unavailable</h1>
                <p class="mt-4 text-muted">{{ $message }}</p>
                <a href="{{ route('admin.pos.registers.index') }}" class="pos-btn pos-btn--primary mt-6 inline-flex">Manage registers</a>
            </div>
        </div>
    </div>
@endsection
