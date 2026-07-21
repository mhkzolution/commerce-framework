@extends('layouts.admin')

@section('title', $tenant->name)

@section('page')
    <x-admin.page :title="$tenant->name" description="Tenant configuration and status.">
        <x-admin.form.shell action="{{ route('admin.platform.tenants.update', $tenant) }}" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Tenant details">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="cf-label">Name</label>
                        <input id="name" name="name" value="{{ old('name', $tenant->name) }}" required class="cf-input mt-1">
                    </div>
                    <div>
                        <label for="slug" class="cf-label">Slug</label>
                        <input id="slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required class="cf-input mt-1">
                        <p class="mt-1 text-xs text-muted">API/storefront header: X-Tenant: {{ $tenant->slug }}</p>
                    </div>
                    <div>
                        <label for="domain" class="cf-label">Domain</label>
                        <input id="domain" name="domain" value="{{ old('domain', $tenant->domain) }}" class="cf-input mt-1">
                    </div>
                    <div>
                        <label for="status" class="cf-label">Status</label>
                        <select id="status" name="status" class="cf-input mt-1">
                            <option value="active" @selected(old('status', $tenant->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $tenant->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.platform.tenants.index')">Back</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
