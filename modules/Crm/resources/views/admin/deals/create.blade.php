@extends('layouts.admin')
@section('title', 'New Deal')
@section('page')
    <x-admin.form.shell action="{{ route('admin.crm.deals.store') }}" method="POST" class="max-w-2xl">
        @csrf
        <x-admin.form.section title="Details">
            <input name="name" class="cf-input" placeholder="Name">
            <input name="title" class="cf-input mt-2" placeholder="Title">
            <input name="slug" class="cf-input mt-2" placeholder="Slug">
            <input name="email" type="email" class="cf-input mt-2" placeholder="Email">
            <textarea name="content" class="cf-input mt-2" rows="4" placeholder="Content"></textarea>
            <select name="status" class="cf-input mt-2">@foreach($statuses as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection