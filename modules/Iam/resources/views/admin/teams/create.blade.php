@extends('layouts.admin')
@section('title', 'New Team')
@section('page')
    <x-admin.form.shell action="{{ route('admin.iam.teams.store') }}" method="POST" class="max-w-xl">
        @csrf
        <x-admin.form.section title="Team details">
            <label class="block text-sm font-medium text-text">Name</label>
            <input name="name" value="{{ old('name') }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug') }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Description</label>
            <textarea name="description" class="cf-input mt-1" rows="3">{{ old('description') }}</textarea>
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status','active')==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create team</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
