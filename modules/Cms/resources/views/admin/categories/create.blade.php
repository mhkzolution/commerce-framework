@extends('layouts.admin')
@section('title', 'New category')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.categories.store') }}" method="POST" class="max-w-2xl">
        @csrf
        <x-admin.form.section title="Category">
            @include('cms::admin.categories._form')
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create category</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
