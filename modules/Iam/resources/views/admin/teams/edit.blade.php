@extends('layouts.admin')
@section('title', 'Edit Team')
@section('page')
    @session('status')
        <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
    @endsession

    <x-admin.form.shell action="{{ route('admin.iam.teams.update', $item) }}" method="POST" class="max-w-xl mb-8">
        @csrf @method('PUT')
        <x-admin.form.section title="Team details">
            <label class="block text-sm font-medium text-text">Name</label>
            <input name="name" value="{{ old('name', $item->name) }}" class="cf-input mt-1" required>
            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug', $item->slug) }}" class="cf-input mt-1">
            <label class="mt-4 block text-sm font-medium text-text">Description</label>
            <textarea name="description" class="cf-input mt-1" rows="3">{{ old('description', $item->description) }}</textarea>
            <label class="mt-4 block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(old('status', $item->status)==$k)>{{ $v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save team</x-admin.button></x-slot:actions>
    </x-admin.form.shell>

    <x-admin.card title="Members">
        <x-admin.table.shell>
            <x-slot:head><tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Role</th><th class="px-4 py-3 text-right">Actions</th></tr></x-slot:head>
            @forelse ($item->users as $member)
                <tr>
                    <td class="px-4 py-3">{{ $member->name }} <span class="text-muted">({{ $member->email }})</span></td>
                    <td class="px-4 py-3">{{ $member->pivot->role }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.iam.teams.members.destroy', [$item, $member]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-danger hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No members.</td></tr>
            @endforelse
        </x-admin.table.shell>

        <form method="POST" action="{{ route('admin.iam.teams.members.store', $item) }}" class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4">
            @csrf
            <select name="user_id" class="cf-input min-w-[12rem]" required>
                <option value="">Select user</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            <select name="role" class="cf-input">
                <option value="member">Member</option>
                <option value="admin">Admin</option>
            </select>
            <x-admin.button variant="secondary" type="submit">Add member</x-admin.button>
        </form>
    </x-admin.card>
@endsection
