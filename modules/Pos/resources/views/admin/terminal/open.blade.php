@extends('layouts.admin')

@section('title', 'Open session — ' . $register->name)

@section('page')
    <x-admin.page :title="$register->name" description="Open a cashier session before starting sales.">
        <x-admin.form.shell action="{{ route('admin.pos.terminal.open', $register) }}" method="POST" class="max-w-md">
            @csrf
            <x-admin.form.section title="Opening float">
                <p class="text-sm text-muted">Enter the starting cash in the drawer (in cents).</p>
                <label class="mt-4 block text-sm font-medium text-text">Opening balance (cents)</label>
                <input name="opening_balance" type="number" min="0" value="{{ old('opening_balance', 0) }}" class="cf-input mt-1">
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.pos.registers.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Open session</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
