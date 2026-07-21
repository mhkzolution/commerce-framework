<?php

declare(strict_types=1);

namespace Commerce\Crm\Http\Controllers\Admin;

use Commerce\Crm\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DealController extends Controller
{
    public function index(): View
    {
        return view('crm::admin.deals.index', [
            'items' => Deal::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('crm::admin.deals.create', [
            'statuses' => config('crm.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Deal::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.crm.deals.edit', $item)->with('status', 'Created.');
    }

    public function edit(Deal $deal): View
    {
        return view('crm::admin.deals.edit', [
            'item' => $deal,
            'statuses' => config('crm.statuses', []),
        ]);
    }

    public function update(Request $request, Deal $deal): RedirectResponse
    {
        $deal->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.crm.deals.edit', $deal)->with('status', 'Saved.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $deal->delete();

        return redirect()->route('admin.crm.deals.index')->with('status', 'Deleted.');
    }
}