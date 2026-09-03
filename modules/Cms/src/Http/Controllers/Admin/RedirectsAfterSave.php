<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsAfterSave
{
    private function redirectAfterSave(Request $request, string $indexRoute, string $editUrl, string $status): RedirectResponse
    {
        if ($request->input('intent') === 'continue') {
            return redirect()->to($editUrl)->with('status', $status);
        }

        return redirect()->route($indexRoute)->with('status', $status);
    }
}
