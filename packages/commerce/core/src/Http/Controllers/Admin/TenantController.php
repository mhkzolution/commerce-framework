<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers\Admin;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function index(): View
    {
        return view('commerce::admin.tenants.index', [
            'items' => Tenant::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('commerce::admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
        ]);

        $tenant = $this->tenantService->create(
            $validated['name'],
            $validated['slug'] ?? null,
            $validated['domain'] ?? null,
        );

        return redirect()
            ->route('admin.platform.tenants.edit', $tenant)
            ->with('status', 'Tenant created.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('commerce::admin.tenants.edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('admin.platform.tenants.edit', $tenant)
            ->with('status', 'Tenant saved.');
    }
}
