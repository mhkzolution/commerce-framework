<?php

declare(strict_types=1);

namespace Commerce\Navigation\Http\Controllers\Admin;

use Commerce\Navigation\Http\Requests\UpdateNavigationMenuRequest;
use Commerce\Navigation\Models\NavigationMenu;
use Commerce\Navigation\Services\NavigationMenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class NavigationMenuController extends Controller
{
    public function __construct(
        private readonly NavigationMenuService $menus,
    ) {}

    public function index(): View
    {
        return view('navigation::admin.index', [
            'menus' => NavigationMenu::query()->orderBy('handle')->get(),
        ]);
    }

    public function edit(NavigationMenu $menu): View
    {
        $menu->load('items');

        return view('navigation::admin.edit', [
            'menu' => $menu,
        ]);
    }

    public function update(UpdateNavigationMenuRequest $request, NavigationMenu $menu): RedirectResponse
    {
        $this->menus->syncItems(
            $menu,
            $request->validated('name'),
            $request->itemsPayload(),
        );

        return redirect()
            ->route('admin.navigation.menus.edit', $menu)
            ->with('status', __('navigation::admin.saved'));
    }
}
