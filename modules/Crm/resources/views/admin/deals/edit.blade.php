@extends('layouts.admin')
@section('title', 'Edit Deal')
@section('page')
    <x-admin.form.shell action="{{ route('admin.crm.deals.update', $item) }}" method="POST" class="max-w-xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Deal details">
            <label class="block text-sm font-medium text-text">Title</label>
            <input name="title" value="{{ old('title', $item->title) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Lead</label>
            <select name="lead_id" class="cf-input mt-1">
                <option value="">No lead</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead->id }}" @selected(old('lead_id', $item->lead_id) == $lead->id)>{{ $lead->name }}</option>
                @endforeach
            </select>
            <label class="mt-4 block text-sm font-medium text-text">Amount (cents)</label>
            <input name="amount" type="number" min="0" value="{{ old('amount', $item->amount) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Stage</label>
            <select name="stage" class="cf-input mt-1">@foreach($stages as $k=>$v)<option value="{{ $k }}" @selected(old('stage', $item->stage)==$k)>{{ $v }}</option>@endforeach</select>
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save deal</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection