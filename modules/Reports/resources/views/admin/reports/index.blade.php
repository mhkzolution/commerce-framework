@extends('layouts.admin')

@section('title', 'รายงาน')

@section('page')
    <x-admin.page title="รายงาน" description="ศูนย์รวมรายงานยอดขาย คำสั่งซื้อ และสินค้า">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[['label' => 'รายงาน', 'active' => true]]" />
        </x-slot:breadcrumb>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($reports as $report)
                <a href="{{ route($report['route']) }}" class="group block rounded-xl border border-border bg-surface p-5 transition hover:border-accent hover:shadow-sm">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-subtle text-accent">
                        <x-admin.icon :name="$report['icon']" class="h-5 w-5" />
                    </div>
                    <h2 class="text-base font-semibold text-text group-hover:text-accent">{{ $report['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ $report['description'] }}</p>
                </a>
            @endforeach
        </div>
    </x-admin.page>
@endsection
