<?php

declare(strict_types=1);

namespace Commerce\Crm\Http\Controllers\Admin;

use Commerce\Crm\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
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
            'statuses' => config('crm.lead_statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Lead::query()->create($this->validated($request));

        return redirect()->route('admin.crm.leads.edit', $item)->with('status', 'Lead created.');
    }

    public function edit(Lead $lead): View
    {
        return view('crm::admin.leads.edit', [
            'item' => $lead,
            'statuses' => config('crm.lead_statuses', []),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update($this->validated($request));

        return redirect()->route('admin.crm.leads.edit', $lead)->with('status', 'Lead saved.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.crm.leads.index')->with('status', 'Lead deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(array_keys(config('crm.lead_statuses', [])))],
        ]);
    }
}
