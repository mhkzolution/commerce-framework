@extends('layouts.admin')

@section('title', 'Webhooks')

@section('page')
    <x-admin.page title="Webhooks" description="Outbound event notifications to external systems.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Webhooks', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            @can('webhooks.webhook.manage')
                <x-admin.button variant="primary" :href="route('admin.webhooks.create')">
                    <x-admin.icon name="plus" class="h-4 w-4" />
                    Add webhook
                </x-admin.button>
            @endcan
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search webhooks..." />
                        </form>
                    </x-slot:search>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">URL</th>
                    <th class="px-4 py-3">Events</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($webhooks as $webhook)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $webhook->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ Str::limit($webhook->url, 60) }}</td>
                    <td class="px-4 py-3 text-muted">{{ count($webhook->events ?? []) }} events</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$webhook->is_active ? 'published' : 'archived'">
                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.webhooks.show', $webhook)">View</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No webhooks yet.</td></tr>
            @endforelse

            @if ($webhooks->hasPages())
                <x-slot:pagination>{{ $webhooks->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
