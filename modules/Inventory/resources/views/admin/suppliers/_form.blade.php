@props(['supplier' => null])

<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $supplier?->name) }}" required class="cf-input mt-1">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $supplier?->email) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $supplier?->phone) }}" class="cf-input mt-1">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-text" for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="cf-input mt-1">{{ old('notes', $supplier?->notes) }}</textarea>
</div>
