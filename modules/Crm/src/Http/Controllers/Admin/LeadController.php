<?php

declare(strict_types=1);

namespace Commerce\Crm\Http\Controllers\Admin;

use Commerce\Crm\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class LeadController extends Controller
{
    public function index(): View
    {
        return view('crm::admin.leads.index', [
            'items' => Lead::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('crm::admin.leads.create', [
            'statuses' => config('crm.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Lead::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.crm.leads.edit', $item)->with('status', 'Created.');
    }

    public function edit(Lead $lead): View
    {
        return view('crm::admin.leads.edit', [
            'item' => $lead,
            'statuses' => config('crm.statuses', []),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.crm.leads.edit', $lead)->with('status', 'Saved.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.crm.leads.index')->with('status', 'Deleted.');
    }
}