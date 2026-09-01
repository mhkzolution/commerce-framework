@extends('layouts.admin')
@section('title', 'Edit Page')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.pages.update', $item) }}" method="POST" class="max-w-2xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Page details">
            <label class="block text-sm font-medium text-text">Title</label>
            <input name="title" value="{{ old('title', $item->title) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug', $item->slug) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Content</label>
            <textarea name="content" class="cf-input mt-1" rows="8">{{ old('content', $item->content) }}</textarea>
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        @include('catalog::admin.partials.seo-fields', ['seo' => $seo ?? null])
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save page</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection