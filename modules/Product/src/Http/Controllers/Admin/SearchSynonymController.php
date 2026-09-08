<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Product\Http\Requests\UpsertSearchSynonymRequest;
use Commerce\Product\Models\SearchSynonym;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SearchSynonymController extends Controller
{
    public function index(): View
    {
        return view('product::admin.search-synonyms.index', [
            'synonyms' => SearchSynonym::query()->orderBy('from_term')->paginate(),
        ]);
    }

    public function store(UpsertSearchSynonymRequest $request): RedirectResponse
    {
        SearchSynonym::query()->create($request->validated());

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym created.');
    }

    public function update(UpsertSearchSynonymRequest $request, int $search_synonym): RedirectResponse
    {
        $synonym = SearchSynonym::query()->findOrFail($search_synonym);
        $synonym->update($request->validated());

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym updated.');
    }

    public function destroy(int $search_synonym): RedirectResponse
    {
        SearchSynonym::query()->findOrFail($search_synonym)->delete();

        return redirect()->route('admin.catalog.search-synonyms.index')
            ->with('status', 'Search synonym deleted.');
    }
}
