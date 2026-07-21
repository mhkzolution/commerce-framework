<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers\Api;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Core\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class TenantApiController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): JsonResponse
    {
        $tenants = Tenant::query()->orderBy('name')->get()->map(static fn (Tenant $tenant): array => [
            'uuid' => $tenant->uuid,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => $tenant->domain,
            'status' => $tenant->status,
        ]);

        return ApiResponse::success($tenants->all());
    }

    public function store(Request $request): JsonResponse
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

        return ApiResponse::success([
            'uuid' => $tenant->uuid,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => $tenant->domain,
            'status' => $tenant->status,
        ], status: 201);
    }

    public function current(): JsonResponse
    {
        $tenant = $this->tenantContext->get();

        if ($tenant === null) {
            return ApiResponse::error('tenant.not_resolved', 'No tenant in current context.', status: 404);
        }

        return ApiResponse::success([
            'uuid' => $tenant->uuid,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => $tenant->domain,
            'status' => $tenant->status,
        ]);
    }

    public function switch(Request $request, string $uuid): JsonResponse
    {
        try {
            $tenant = $this->tenantService->findOrFail($uuid);
            $this->tenantService->setCurrent($tenant);
        } catch (\Throwable $exception) {
            throw new DomainException($exception->getMessage(), previous: $exception);
        }

        return ApiResponse::success([
            'uuid' => $tenant->uuid,
            'slug' => $tenant->slug,
            'message' => 'Tenant context switched for this request.',
        ]);
    }
}
