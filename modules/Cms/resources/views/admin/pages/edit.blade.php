@extends('layouts.admin')
@section('title', 'Edit Page')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.pages.update', $item) }}" method="POST" class="max-w-6xl">
        @csrf @method('PUT')
        @include('cms::admin.pages._form')
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save page</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
