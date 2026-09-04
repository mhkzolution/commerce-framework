@extends('layouts.admin')
@section('title', 'Edit Register')
@section('page')
    <x-admin.form.shell action="{{ route('admin.pos.registers.update', $item) }}" method="POST" class="max-w-xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Register details">
            <label class="block text-sm font-medium text-text">Name</label>
            <input name="name" value="{{ old('name', $item->name) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Code</label>
            <input name="code" value="{{ old('code', $item->code) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Location</label>
            <input name="location" value="{{ old('location', $item->location) }}" class="cf-input mt-1">
            <label class="mt-4 flex items-center gap-2 text-sm text-text">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Active
            </label>
        </x-admin.form.section>
        <x-slot:actions>
            @if ($item->is_active)
                <x-admin.button variant="secondary" :href="route('pos.index', ['register' => $item->uuid])">Open POS</x-admin.button>
            @endif
            <x-admin.button variant="primary" type="submit">Save register</x-admin.button>
        </x-slot:actions>
    </x-admin.form.shell>
@endsection