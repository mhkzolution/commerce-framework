@extends('layouts.admin')

@section('title', $webhook->name)

@section('page')
    <x-admin.page :title="$webhook->name" :description="$webhook->url">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Webhooks', 'url' => route('admin.webhooks.index')],
                ['label' => $webhook->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            <x-admin.badge :variant="$webhook->is_active ? 'published' : 'archived'">
                {{ $webhook->is_active ? 'Active' : 'Inactive' }}
            </x-admin.badge>
        </x-slot:filters>

        <x-slot:primaryActions>
            @can('webhooks.webhook.manage')
                <x-admin.button variant="primary" :href="route('admin.webhooks.edit', $webhook)">Edit</x-admin.button>
            @endcan
        </x-slot:primaryActions>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Details">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-muted">Status</dt>
                        <dd class="mt-1 font-medium text-text">{{ $webhook->is_active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Signing secret</dt>
                        <dd class="mt-1 break-all font-mono text-xs text-text-secondary">{{ $webhook->secret }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Subscribed events</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            @foreach ($webhook->events ?? [] as $event)
                                <x-admin.badge variant="info">{{ $event }}</x-admin.badge>
                            @endforeach
                        </dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Payload format">
                <pre class="cf-code-block">{
  "event": "order.confirmed",
  "occurred_at": "2026-07-21T12:00:00+00:00",
  "tenant_id": null,
  "data": { ... }
}</pre>
                <p class="mt-3 text-xs text-muted">
                    Verify with HMAC-SHA256 of the raw JSON body using your signing secret.
                    Header: {{ config('webhooks.signature_header', 'X-Commerce-Signature') }}
                </p>
            </x-admin.card>
        </div>

        <div class="mt-6">
            <h2 class="mb-4 text-lg font-semibold text-text">Recent deliveries</h2>
            <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">HTTP</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:head>

                @forelse ($deliveries as $delivery)
                    <tr>
                        <td class="px-4 py-3 font-medium text-text">{{ $delivery->event_name }}</td>
                        <td class="px-4 py-3">
                            @php
                                $deliveryBadge = match ($delivery->status) {
                                    'success' => 'published',
                                    'failed' => 'danger',
                                    default => 'draft',
                                };
                            @endphp
                            <x-admin.badge :variant="$deliveryBadge">{{ ucfirst($delivery->status) }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $delivery->response_status ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $delivery->duration_ms !== null ? $delivery->duration_ms . ' ms' : '—' }}</td>
                        <td class="px-4 py-3 text-muted">{{ $delivery->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('webhooks.webhook.manage')
                                @if ($delivery->status === 'failed')
                                    <form method="POST" action="{{ route('admin.webhooks.deliveries.retry', [$webhook, $delivery]) }}" class="inline">
                                        @csrf
                                        <x-admin.button variant="link" type="submit">Retry</x-admin.button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                    @if ($delivery->error_message)
                        <tr>
                            <td colspan="6" class="cf-delivery-error px-4 py-2 text-xs">{{ $delivery->error_message }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No deliveries yet.</td></tr>
                @endforelse

                @if ($deliveries->hasPages())
                    <x-slot:pagination>{{ $deliveries->links() }}</x-slot:pagination>
                @endif
            </x-admin.table.shell>
        </div>
    </x-admin.page>
@endsection
