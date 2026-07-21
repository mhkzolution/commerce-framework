<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers\Admin;

use Commerce\Pos\Models\Register;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class RegisterController extends Controller
{
    public function index(): View
    {
        return view('pos::admin.registers.index', [
            'items' => Register::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('pos::admin.registers.create', [
            'statuses' => config('pos.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Register::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.pos.registers.edit', $item)->with('status', 'Created.');
    }

    public function edit(Register $register): View
    {
        return view('pos::admin.registers.edit', [
            'item' => $register,
            'statuses' => config('pos.statuses', []),
        ]);
    }

    public function update(Request $request, Register $register): RedirectResponse
    {
        $register->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.pos.registers.edit', $register)->with('status', 'Saved.');
    }

    public function destroy(Register $register): RedirectResponse
    {
        $register->delete();

        return redirect()->route('admin.pos.registers.index')->with('status', 'Deleted.');
    }
}