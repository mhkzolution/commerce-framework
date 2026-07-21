@extends('layouts.admin')

@section('title', 'Notification Templates')

@section('page')
    <x-admin.page title="Notification Templates" description="Manage email subjects and views for transactional messages.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'System'],
                ['label' => 'Templates', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($templates as $template)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm text-text">{{ $template->code }}</td>
                    <td class="px-4 py-3 text-text">{{ $template->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $template->channel }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$template->is_active ? 'published' : 'archived'">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.notification.templates.edit', $template)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No templates. Run the notification seeder.</td></tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
