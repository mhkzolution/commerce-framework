@extends('layouts.admin')
@section('title', 'Edit Session')
@section('page')
    <x-admin.form.shell action="{{ route('admin.pos.sessions.update', $item) }}" method="POST" class="max-w-xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Session details">
            <label class="block text-sm font-medium text-text">Register</label>
            <select name="register_id" class="cf-input mt-1" required>
                @foreach($registers as $register)
                    <option value="{{ $register->id }}" @selected(old('register_id', $item->register_id) == $register->id)>{{ $register->name }} ({{ $register->code }})</option>
                @endforeach
            </select>
            <label class="mt-4 block text-sm font-medium text-text">Opened by</label>
            <input name="opened_by" value="{{ old('opened_by', $item->opened_by) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Opened at</label>
            <input name="opened_at" type="datetime-local" value="{{ old('opened_at', optional($item->opened_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Closed at</label>
            <input name="closed_at" type="datetime-local" value="{{ old('closed_at', optional($item->closed_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save session</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection