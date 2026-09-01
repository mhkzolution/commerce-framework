@extends('layouts.admin')
@section('title', 'Edit Post')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.posts.update', $item) }}" method="POST" class="max-w-2xl">
        @csrf @method('PUT')
        @include('cms::admin.posts._form')
        <x-slot:actions>
            @if (! empty($previewUrl))
                <x-admin.button variant="secondary" :href="$previewUrl" target="_blank">Preview</x-admin.button>
            @endif
            <x-admin.button variant="primary" type="submit">Save post</x-admin.button>
        </x-slot:actions>
    </x-admin.form.shell>
@endsection
