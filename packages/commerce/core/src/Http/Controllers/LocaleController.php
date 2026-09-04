<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

final class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $available = array_keys(config('admin.locale.available', ['th' => 'ไทย', 'en' => 'English']));

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($available)],
        ]);

        $locale = $validated['locale'];
        $request->session()->put((string) config('admin.locale.session_key', 'commerce.locale'), $locale);
        app()->setLocale($locale);

        return redirect()->back();
    }
}
