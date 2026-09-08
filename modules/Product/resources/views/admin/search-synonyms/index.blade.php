@extends('layouts.admin')

@section('title', __('catalog::admin.search_synonyms'))

@section('page')
    <x-admin.page :title="__('catalog::admin.search_synonyms')" description="Map search terms to equivalent catalog language.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('catalog::admin.catalog'), 'url' => route('admin.catalog.index')],
                ['label' => __('catalog::admin.search_synonyms'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.card title="Add search synonym">
            <form method="POST" action="{{ route('admin.catalog.search-synonyms.store') }}" class="grid max-w-3xl gap-3 sm:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="from_term" value="{{ old('from_term') }}" placeholder="Search term" required class="cf-input">
                <input name="to_term" value="{{ old('to_term') }}" placeholder="Equivalent term" required class="cf-input">
                <x-admin.button variant="primary" type="submit">Add synonym</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.table.shell class="mt-6">
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Search term</th>
                    <th class="px-4 py-3">Equivalent term</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($synonyms as $synonym)
                @php($updateForm = 'update-synonym-'.$synonym->id)
                <tr>
                    <td class="px-4 py-3">
                        <input form="{{ $updateForm }}" name="from_term" value="{{ $synonym->from_term }}" required class="cf-input">
                    </td>
                    <td class="px-4 py-3">
                        <input form="{{ $updateForm }}" name="to_term" value="{{ $synonym->to_term }}" required class="cf-input">
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form id="{{ $updateForm }}" method="POST" action="{{ route('admin.catalog.search-synonyms.update', $synonym->id) }}" class="inline">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="text-sm text-primary hover:underline">Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.catalog.search-synonyms.destroy', $synonym->id) }}" class="ml-3 inline" onsubmit="return confirm('Delete this search synonym?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-danger hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No search synonyms yet.</td></tr>
            @endforelse

            @if ($synonyms->hasPages())
                <x-slot:pagination>{{ $synonyms->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
