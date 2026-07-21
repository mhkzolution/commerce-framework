<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers\Admin;

use Commerce\Pos\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function index(): View
    {
        return view('pos::admin.sessions.index', [
            'items' => Session::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('pos::admin.sessions.create', [
            'statuses' => config('pos.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Session::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.pos.sessions.edit', $item)->with('status', 'Created.');
    }

    public function edit(Session $session): View
    {
        return view('pos::admin.sessions.edit', [
            'item' => $session,
            'statuses' => config('pos.statuses', []),
        ]);
    }

    public function update(Request $request, Session $session): RedirectResponse
    {
        $session->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.pos.sessions.edit', $session)->with('status', 'Saved.');
    }

    public function destroy(Session $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.pos.sessions.index')->with('status', 'Deleted.');
    }
}