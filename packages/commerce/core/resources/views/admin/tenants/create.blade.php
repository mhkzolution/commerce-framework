@extends('layouts.admin')

@section('title', 'New Tenant')

@section('page')
    <x-admin.page title="New tenant" description="Create a tenant for multi-store SaaS.">
        <x-admin.form.shell action="{{ route('admin.platform.tenants.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Tenant details">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="cf-label">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="cf-input mt-1">
                    </div>
                    <div>
                        <label for="slug" class="cf-label">Slug</label>
                        <input id="slug" name="slug" value="{{ old('slug') }}" class="cf-input mt-1" placeholder="auto-generated if empty">
                    </div>
                    <div>
                        <label for="domain" class="cf-label">Domain</label>
                        <input id="domain" name="domain" value="{{ old('domain') }}" class="cf-input mt-1" placeholder="shop.example.com">
                    </div>
                </div>
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.platform.tenants.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create tenant</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
