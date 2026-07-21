@extends('layouts.admin')

@section('title', 'Audit log')

@section('page')
    <x-admin.page title="Audit log" description="Security and IAM activity recorded across the platform.">
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 text-muted">{{ $item->created_at?->format('M j, Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $item->user?->email ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $item->action }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-muted">No audit entries yet.</td>
                </tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
