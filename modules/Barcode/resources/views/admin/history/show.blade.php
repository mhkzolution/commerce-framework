@extends('layouts.admin')

@section('title', __('barcode::admin.history.view'))

@section('page')
    <x-admin.page :title="__('barcode::admin.history.view')" :description="$job->template_name">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('barcode::admin.title'), 'href' => route('admin.barcode.index')],
                ['label' => __('barcode::admin.history.title'), 'href' => route('admin.barcode.history.index')],
                ['label' => $job->printed_at?->format('Y-m-d H:i') ?? '—', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.barcode.print.show', $job)">
                {{ __('barcode::admin.preview.print') }}
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.card>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">{{ __('barcode::admin.history.date') }}</dt>
                    <dd class="mt-1 text-sm text-text">{{ $job->printed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">{{ __('barcode::admin.history.printed_by') }}</dt>
                    <dd class="mt-1 text-sm text-text">{{ $job->printedBy?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">{{ __('barcode::admin.history.label_count') }}</dt>
                    <dd class="mt-1 text-sm text-text">{{ number_format($job->label_count) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted">{{ __('barcode::admin.history.template') }}</dt>
                    <dd class="mt-1 text-sm text-text">{{ $job->template_name }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-text">{{ __('barcode::admin.queue.title') }}</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($job->payload['lines'] ?? [] as $line)
                        <li class="rounded-lg border border-border px-4 py-3 text-sm">
                            <div class="font-medium text-text">{{ $line['product_name'] ?? '—' }}</div>
                            <div class="text-muted">{{ $line['owner_name'] ?? '—' }} · {{ $line['sku'] ?? '—' }} · ×{{ $line['quantity'] ?? 1 }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-admin.card>
    </x-admin.page>
@endsection
