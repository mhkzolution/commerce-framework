<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers\Admin;

use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function index(): View
    {
        return view('pos::admin.sessions.index', [
            'items' => Session::query()->with('register')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('pos::admin.sessions.create', [
            'registers' => Register::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('pos.session_statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Session::query()->create($this->validated($request));

        return redirect()->route('admin.pos.sessions.edit', $item)->with('status', 'Session created.');
    }

    public function edit(Session $session): View
    {
        return view('pos::admin.sessions.edit', [
            'item' => $session,
            'registers' => Register::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('pos.session_statuses', []),
        ]);
    }

    public function update(Request $request, Session $session): RedirectResponse
    {
        $session->update($this->validated($request));

        return redirect()->route('admin.pos.sessions.edit', $session)->with('status', 'Session saved.');
    }

    public function destroy(Session $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.pos.sessions.index')->with('status', 'Session deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'register_id' => ['required', 'integer', 'exists:pos_registers,id'],
            'opened_by' => ['nullable', 'string', 'max:255'],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'status' => ['required', 'string', Rule::in(array_keys(config('pos.session_statuses', [])))],
        ]);
    }
}
