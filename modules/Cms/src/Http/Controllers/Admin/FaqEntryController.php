<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\UpsertFaqEntryData;
use Commerce\Cms\Http\Requests\UpsertFaqEntryRequest;
use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Services\FaqEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class FaqEntryController extends Controller
{
    use RedirectsAfterSave;

    public function __construct(
        private readonly FaqEntryService $entries,
    ) {}

    public function index(): View
    {
        return view('cms::admin.faq-entries.index', [
            'items' => FaqEntry::query()->orderBy('sort_order')->orderByDesc('id')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.faq-entries.create');
    }

    public function store(UpsertFaqEntryRequest $request): RedirectResponse
    {
        $item = $this->entries->create($this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.faq-entries.index',
            route('admin.cms.faq-entries.edit', $item),
            'FAQ entry created.',
        );
    }

    public function edit(FaqEntry $faqEntry): View
    {
        return view('cms::admin.faq-entries.edit', ['item' => $faqEntry]);
    }

    public function update(UpsertFaqEntryRequest $request, FaqEntry $faqEntry): RedirectResponse
    {
        $this->entries->update($faqEntry, $this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.faq-entries.index',
            route('admin.cms.faq-entries.edit', $faqEntry),
            'FAQ entry saved.',
        );
    }

    public function destroy(FaqEntry $faqEntry): RedirectResponse
    {
        $this->entries->delete($faqEntry);

        return redirect()->route('admin.cms.faq-entries.index')->with('status', 'FAQ entry deleted.');
    }

    private function toData(UpsertFaqEntryRequest $request): UpsertFaqEntryData
    {
        return new UpsertFaqEntryData(
            question: $request->validated('question'),
            answer: $request->validated('answer'),
            sortOrder: (int) ($request->validated('sort_order') ?? 0),
            isActive: $request->boolean('is_active'),
        );
    }
}
