@php
    $webhook = $webhook ?? null;
    $selectedEvents = old('events', $webhook?->events ?? []);
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $webhook?->name) }}" required class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="url">Endpoint URL</label>
        <input id="url" type="url" name="url" value="{{ old('url', $webhook?->url) }}" required placeholder="https://example.com/webhooks/commerce" class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="secret">Signing secret</label>
        <input id="secret" name="secret" value="{{ old('secret') }}" @if ($webhook) placeholder="Leave blank to keep current secret" @endif class="cf-input mt-1 font-mono">
        <p class="mt-1 text-xs text-muted">Used for HMAC-SHA256 signature in the {{ config('webhooks.signature_header', 'X-Commerce-Signature') }} header. Auto-generated if left empty on create.</p>
    </div>
    <div class="sm:col-span-2">
        <span class="block text-sm font-medium text-text">Events</span>
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach ($availableEvents as $eventKey => $eventLabel)
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input type="checkbox" name="events[]" value="{{ $eventKey }}" @checked(in_array($eventKey, $selectedEvents, true)) class="rounded border-border">
                    <span>{{ $eventLabel }} <span class="text-muted">({{ $eventKey }})</span></span>
                </label>
            @endforeach
        </div>
        @error('events')
            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $webhook?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
