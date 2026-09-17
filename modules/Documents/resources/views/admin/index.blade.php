@extends('layouts.admin')

@section('title', __('documents::admin.documents_title'))

@section('page')
    <x-admin.page :title="__('documents::admin.documents_title')" :description="__('documents::admin.documents_description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => __('documents::admin.documents_title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" :placeholder="__('documents::admin.search_placeholder')" />
                        </form>
                    </x-slot:search>
                    <x-slot:filters>
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <select name="type" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">{{ __('documents::admin.all_types') }}</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">{{ __('documents::admin.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('documents::admin.number') }}</th>
                    <th class="px-4 py-3">{{ __('documents::admin.type') }}</th>
                    <th class="px-4 py-3">{{ __('documents::admin.related') }}</th>
                    <th class="px-4 py-3">{{ __('documents::admin.buyer') }}</th>
                    <th class="px-4 py-3">{{ __('documents::admin.total') }}</th>
                    <th class="px-4 py-3">{{ __('documents::admin.status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('documents::admin.actions') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($documents as $document)
                @php
                    $payload = \Commerce\Documents\Support\DocumentPayloadView::fromDocument($document);
                    $statusVariant = $document->isIssued() ? 'published' : 'archived';
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $document->number }}</div>
                        <div class="text-xs text-muted">{{ $document->issued_at?->format('Y-m-d H:i') }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $types[$document->type->value] ?? $document->type->value }}</td>
                    <td class="px-4 py-3 text-muted">{{ $payload->relatedOrderNumber() ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $payload->buyerName() ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $payload->money('grand_total') }} {{ $payload->currency() }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$statusVariant">
                            {{ $statuses[$document->status->value] ?? $document->status->value }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.documents.show', $document)">{{ __('documents::admin.view') }}</x-admin.button>
                        <x-admin.button variant="link" :href="route('admin.documents.print', $document)">{{ __('documents::admin.print') }}</x-admin.button>
                        <x-admin.button variant="link" :href="route('admin.documents.download', $document)">{{ __('documents::admin.download') }}</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">{{ __('documents::admin.empty') }}</td></tr>
            @endforelse

            @if ($documents->hasPages())
                <x-slot:pagination>{{ $documents->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
