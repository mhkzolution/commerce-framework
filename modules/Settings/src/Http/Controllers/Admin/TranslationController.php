<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Settings\Http\Requests\UpdateTranslationFileRequest;
use Commerce\Settings\Services\TranslationCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationCatalogService $translationCatalog,
    ) {}

    public function index(Request $request): View
    {
        $locale = $request->string('locale')->toString() ?: config('admin.locale.default', 'th');

        return view('settings::admin.translations.index', [
            'locale' => $locale,
            'locales' => config('admin.locale.available', []),
            'files' => $this->translationCatalog->files($locale),
        ]);
    }

    public function edit(Request $request, string $namespace, string $file): View
    {
        $locale = $request->string('locale')->toString() ?: config('admin.locale.default', 'th');
        $search = trim($request->string('search')->toString());

        $translations = $this->translationCatalog->load($namespace, $file, $locale);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $translations = array_filter(
                $translations,
                static fn (string $value, string $key): bool => str_contains(mb_strtolower($key), $needle)
                    || str_contains(mb_strtolower($value), $needle),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        return view('settings::admin.translations.edit', [
            'namespace' => $namespace,
            'file' => $file,
            'locale' => $locale,
            'locales' => config('admin.locale.available', []),
            'search' => $search,
            'translations' => $translations,
            'label' => $namespace.'::'.$file,
        ]);
    }

    public function update(UpdateTranslationFileRequest $request, string $namespace, string $file): RedirectResponse
    {
        $locale = $request->validated('locale');
        $submitted = $request->validated('translations');

        $existing = $this->translationCatalog->load($namespace, $file, $locale);
        $merged = array_merge($existing, $submitted);

        $this->translationCatalog->save($namespace, $file, $locale, $merged);

        return redirect()
            ->route('admin.settings.translations.edit', [
                'namespace' => $namespace,
                'file' => $file,
                'locale' => $locale,
            ])
            ->with('status', __('settings::admin.translations_saved'));
    }
}
