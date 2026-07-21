@extends('layouts.admin')

@section('title', 'Edit webhook')

@section('page')
    <x-admin.page title="Edit webhook" :description="$webhook->name">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Webhooks', 'url' => route('admin.webhooks.index')],
                ['label' => $webhook->name, 'url' => route('admin.webhooks.show', $webhook)],
                ['label' => 'Edit', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}" onsubmit="return confirm('Delete this webhook?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.webhooks.update', $webhook) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Webhook details">
                @include('webhooks::admin._form', ['webhook' => $webhook, 'availableEvents' => $availableEvents])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.webhooks.show', $webhook)">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
