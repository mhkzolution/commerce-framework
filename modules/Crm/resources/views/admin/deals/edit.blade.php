@extends('layouts.admin')
@section('title', 'Edit')
@section('page')
    <x-admin.form.shell action="{{ route('admin.crm.deals.update', $item) }}" method="POST" class="max-w-2xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Details">
            <input name="name" value="{{ old('name', $item->name) }}" class="cf-input" placeholder="Name">
            <input name="title" value="{{ old('title', $item->title) }}" class="cf-input mt-2" placeholder="Title">
            <input name="slug" value="{{ old('slug', $item->slug) }}" class="cf-input mt-2" placeholder="Slug">
            <input name="email" value="{{ old('email', $item->email) }}" type="email" class="cf-input mt-2" placeholder="Email">
            <textarea name="content" class="cf-input mt-2" rows="4">{{ old('content', $item->content) }}</textarea>
            <select name="status" class="cf-input mt-2">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection