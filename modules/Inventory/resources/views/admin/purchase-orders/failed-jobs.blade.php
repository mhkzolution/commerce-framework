@extends('layouts.admin')

@section('title', 'Failed PO Emails')

@section('page')
    <x-admin.page title="Failed purchase order emails" description="Queued supplier emails that failed to send.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Purchase orders', 'url' => route('admin.inventory.purchase-orders.index')],
                ['label' => 'Failed emails', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.index')">Back to purchase orders</x-admin.button>
        </x-slot:secondaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Job</th>
                    <th class="px-4 py-3">Queue</th>
                    <th class="px-4 py-3">Failed at</th>
                    <th class="px-4 py-3">Error</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($failedJobs as $job)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $job->summary }}</td>
                    <td class="px-4 py-3 text-muted">{{ $job->queue }}</td>
                    <td class="px-4 py-3 text-muted">{{ $job->failed_at }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $job->exception }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-3">
                            <form method="POST" action="{{ route('admin.inventory.purchase-orders.failed-jobs.retry', $job->uuid) }}">
                                @csrf
                                <button type="submit" class="text-sm text-accent hover:underline">Retry</button>
                            </form>
                            <form method="POST" action="{{ route('admin.inventory.purchase-orders.failed-jobs.destroy', $job->uuid) }}" onsubmit="return confirm('Remove this failed job?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-muted hover:text-danger">Dismiss</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No failed purchase order emails.</td></tr>
            @endforelse

            @if ($failedJobs->hasPages())
                <x-slot:pagination>{{ $failedJobs->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
