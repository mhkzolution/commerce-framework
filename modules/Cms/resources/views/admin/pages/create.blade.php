@extends('layouts.admin')
@section('title', 'New Page')
@section('page')
    <x-admin.form.shell action="{{ route('admin.cms.pages.store') }}" method="POST" class="cms-workspace-form">
        @csrf
        @include('cms::admin.pages._form')
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create page</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
