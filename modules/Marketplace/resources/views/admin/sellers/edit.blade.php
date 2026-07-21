@extends('layouts.admin')
@section('title', 'Edit Seller')
@section('page')
    <x-admin.form.shell action="{{ route('admin.marketplace.sellers.update', $item) }}" method="POST" class="max-w-xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Seller details">
            <label class="block text-sm font-medium text-text">Name</label>
            <input name="name" value="{{ old('name', $item->name) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug', $item->slug) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Email</label>
            <input name="email" type="email" value="{{ old('email', $item->email) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Commission rate (basis points)</label>
            <input name="commission_rate" type="number" min="0" max="10000" value="{{ old('commission_rate', $item->commission_rate) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save seller</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection