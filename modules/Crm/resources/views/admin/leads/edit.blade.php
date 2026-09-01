@extends('layouts.admin')
@section('title', 'Edit Lead')
@section('page')
    @if (in_array($item->status, ['new', 'contacted'], true))
        <form method="POST" action="{{ route('admin.crm.leads.qualify', $item) }}" class="mb-4">
            @csrf
            <x-admin.button variant="secondary" type="submit">Qualify lead</x-admin.button>
        </form>
    @endif

    @if ($item->status === 'qualified')
        <x-admin.form.shell action="{{ route('admin.crm.leads.convert', $item) }}" method="POST" class="mb-6 max-w-xl rounded-lg border border-border bg-surface p-4">
            @csrf
            <x-admin.form.section title="Convert to deal">
                <label class="block text-sm font-medium text-text">Deal title</label>
                <input name="title" value="{{ old('title', $item->name . ' — Deal') }}" class="cf-input mt-1" required>
                <label class="mt-4 block text-sm font-medium text-text">Amount (cents)</label>
                <input name="amount" type="number" min="0" value="{{ old('amount', 0) }}" class="cf-input mt-1" required>
            </x-admin.form.section>
            <x-slot:actions><x-admin.button variant="primary" type="submit">Convert to deal</x-admin.button></x-slot:actions>
        </x-admin.form.shell>
    @endif

    <x-admin.form.shell action="{{ route('admin.crm.leads.update', $item) }}" method="POST" class="max-w-xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Lead details">
            <label class="block text-sm font-medium text-text">Name</label>
            <input name="name" value="{{ old('name', $item->name) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Email</label>
            <input name="email" type="email" value="{{ old('email', $item->email) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Phone</label>
            <input name="phone" value="{{ old('phone', $item->phone) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Source</label>
            <input name="source" value="{{ old('source', $item->source) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save lead</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
