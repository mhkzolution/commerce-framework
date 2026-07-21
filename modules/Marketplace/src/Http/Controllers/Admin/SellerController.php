<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Controllers\Admin;

use Commerce\Marketplace\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $item = Seller::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.marketplace.sellers.edit', $item)->with('status', 'Created.');
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
        $seller->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.marketplace.sellers.edit', $seller)->with('status', 'Saved.');
    }

    public function destroy(Seller $seller): RedirectResponse
    {
        $seller->delete();

        return redirect()->route('admin.marketplace.sellers.index')->with('status', 'Deleted.');
    }
}