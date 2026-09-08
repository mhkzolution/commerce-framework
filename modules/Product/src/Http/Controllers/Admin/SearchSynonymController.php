<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SearchSynonymController extends Controller
{
    public function index(): View
    {
        return view('product::admin.search-synonyms.index', [
            'synonyms' => SearchSynonym::query()->orderBy('from_term')->paginate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->normalize($request));

        $validated = $request->validate([
            'from_term' => ['required', 'string', 'max:255', 'unique:product_search_synonyms,from_term'],
            'to_term' => ['required', 'string', 'max:255'],
        ]);

        SearchSynonym::query()->create($validated);

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym created.');
    }

    public function update(Request $request, int $search_synonym): RedirectResponse
    {
        $synonym = SearchSynonym::query()->findOrFail($search_synonym);
        $request->merge($this->normalize($request));

        $validated = $request->validate([
            'from_term' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_search_synonyms', 'from_term')->ignore($synonym->id),
            ],
            'to_term' => ['required', 'string', 'max:255'],
        ]);

        $synonym->update($validated);

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym updated.');
    }

    public function destroy(int $search_synonym): RedirectResponse
    {
        SearchSynonym::query()->findOrFail($search_synonym)->delete();

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym deleted.');
    }

    /**
     * @return array{from_term: string, to_term: string}
     */
    private function normalize(Request $request): array
    {
        return [
            'from_term' => SearchNormalizer::textNormalize((string) $request->input('from_term')),
            'to_term' => SearchNormalizer::textNormalize((string) $request->input('to_term')),
        ];
    }
}
