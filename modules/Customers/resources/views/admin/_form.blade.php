<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $customer?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $customer?->email) }}" required class="cf-input mt-1">
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="status">Status</label>
        <select id="status" name="status" class="cf-input mt-1">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $customer?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
