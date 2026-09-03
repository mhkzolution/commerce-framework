@props([
    'jobs' => [],
])

<x-admin.table.shell>
    <x-slot:head>
        <tr class="text-left text-xs uppercase tracking-wide text-muted">
            <th class="px-4 py-3">{{ __('barcode::admin.history.date') }}</th>
            <th class="px-4 py-3">{{ __('barcode::admin.history.printed_by') }}</th>
            <th class="px-4 py-3">{{ __('barcode::admin.history.label_count') }}</th>
            <th class="px-4 py-3">{{ __('barcode::admin.history.template') }}</th>
            <th class="px-4 py-3">{{ __('barcode::admin.history.paper_size') }}</th>
            <th class="px-4 py-3">{{ __('barcode::admin.history.status') }}</th>
            <th class="px-4 py-3 text-right">{{ __('barcode::admin.templates.actions') }}</th>
        </tr>
    </x-slot:head>

    @forelse ($jobs as $job)
        <tr>
            <td class="px-4 py-3 text-sm text-text">{{ $job['printed_at'] }}</td>
            <td class="px-4 py-3 text-sm text-text">{{ $job['printed_by'] }}</td>
            <td class="px-4 py-3 text-sm text-text">{{ number_format($job['label_count']) }}</td>
            <td class="px-4 py-3 text-sm text-muted">{{ $job['template'] }}</td>
            <td class="px-4 py-3 text-sm text-muted">{{ $job['paper_size'] }}</td>
            <td class="px-4 py-3">
                <x-admin.badge :variant="$job['status'] === 'completed' ? 'published' : 'danger'">
                    {{ $job['status'] === 'completed' ? __('barcode::admin.history.status_completed') : __('barcode::admin.history.status_failed') }}
                </x-admin.badge>
            </td>
            <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                    <x-admin.button variant="ghost" :href="route('admin.barcode.history.show', $job['uuid'])">
                        {{ __('barcode::admin.history.view') }}
                    </x-admin.button>
                    @can('barcode.history.reprint')
                        <x-admin.button
                            variant="secondary"
                            type="button"
                            data-bc-history-reprint
                            data-reprint-url="{{ route('admin.barcode.history.reprint', $job['uuid']) }}"
                        >
                            {{ __('barcode::admin.history.reprint') }}
                        </x-admin.button>
                    @endcan
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="px-4 py-8 text-center text-sm text-muted">—</td>
        </tr>
    @endforelse
</x-admin.table.shell>
