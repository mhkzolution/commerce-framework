<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Controllers\Admin;

use Commerce\Marketplace\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SellerController extends Controller
{
    public function index(): View
    {
        return view('marketplace::admin.sellers.index', [
            'items' => Seller::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('marketplace::admin.sellers.create', [
            'statuses' => config('marketplace.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Seller::query()->create($this->validated($request));

        return redirect()->route('admin.marketplace.sellers.edit', $item)->with('status', 'Seller created.');
    }

    public function edit(Seller $seller): View
    {
        return view('marketplace::admin.sellers.edit', [
            'item' => $seller,
            'statuses' => config('marketplace.statuses', []),
        ]);
    }

    public function update(Request $request, Seller $seller): RedirectResponse
    {
        $seller->update($this->validated($request, $seller->uuid));

        return redirect()->route('admin.marketplace.sellers.edit', $seller)->with('status', 'Seller saved.');
    }

    public function destroy(Seller $seller): RedirectResponse
    {
        $seller->delete();

        return redirect()->route('admin.marketplace.sellers.index')->with('status', 'Seller deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $uuid = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('marketplace_sellers', 'slug')->ignore($uuid, 'uuid')],
            'email' => ['nullable', 'email', 'max:255'],
            'commission_rate' => ['required', 'integer', 'min:0', 'max:10000'],
            'status' => ['required', 'string', Rule::in(array_keys(config('marketplace.statuses', [])))],
        ]);

        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        return $data;
    }
}
