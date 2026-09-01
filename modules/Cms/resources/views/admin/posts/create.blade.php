@extends('layouts.admin')
@section('title', 'New Post')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.posts.store') }}" method="POST" class="max-w-2xl">
        @csrf
        @include('cms::admin.posts._form')
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create post</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
