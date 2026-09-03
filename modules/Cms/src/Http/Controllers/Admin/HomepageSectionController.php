<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\Http\Requests\UpdateHomepageSectionsRequest;
use Commerce\Cms\Models\HomepageSection;
use Commerce\Cms\Services\HomepageSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class HomepageSectionController extends Controller
{
    public function __construct(
        private readonly HomepageSectionService $sections,
    ) {}

    public function edit(): View
    {
        return view('cms::admin.homepage.edit', [
            'sections' => $this->sections->ensureDefaults(),
            'layouts' => [
                HomepageSection::LAYOUT_SLIDER => __('cms::admin.layout_slider'),
                HomepageSection::LAYOUT_GRID => __('cms::admin.layout_grid'),
                HomepageSection::LAYOUT_FULL_WIDTH => __('cms::admin.layout_full_width'),
            ],
        ]);
    }

    public function update(UpdateHomepageSectionsRequest $request): RedirectResponse
    {
        $rows = [];
        foreach ($request->validated('sections') as $row) {
            $rows[] = [
                'uuid' => $row['uuid'],
                'layout' => $row['layout'],
                'sort_order' => (int) $row['sort_order'],
                'is_active' => in_array($row['is_active'] ?? false, [true, 1, '1'], true),
                'columns' => isset($row['columns']) ? (int) $row['columns'] : null,
            ];
        }

        $this->sections->updateMany($rows);

        return redirect()->route('admin.cms.homepage.edit')->with('status', 'Homepage layout saved.');
    }
}
