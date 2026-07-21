@extends('layouts.admin')

@section('title', 'Add webhook')

@section('page')
    <x-admin.page title="Add webhook" description="Subscribe to outbound Commerce Framework events.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Webhooks', 'url' => route('admin.webhooks.index')],
                ['label' => 'Add webhook', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.webhooks.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Webhook details">
                @include('webhooks::admin._form', ['availableEvents' => $availableEvents])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.webhooks.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create webhook</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
