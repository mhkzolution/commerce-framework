<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers\Admin;

use Commerce\Pos\Models\Register;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
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
        return view('pos::admin.registers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Register::query()->create($this->validated($request));

        return redirect()->route('admin.pos.registers.edit', $item)->with('status', 'Register created.');
    }

    public function edit(Register $register): View
    {
        return view('pos::admin.registers.edit', [
            'item' => $register,
        ]);
    }

    public function update(Request $request, Register $register): RedirectResponse
    {
        $register->update($this->validated($request, $register->uuid));

        return redirect()->route('admin.pos.registers.edit', $register)->with('status', 'Register saved.');
    }

    public function destroy(Register $register): RedirectResponse
    {
        $register->delete();

        return redirect()->route('admin.pos.registers.index')->with('status', 'Register deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $uuid = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('pos_registers', 'code')->ignore($uuid, 'uuid')],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
