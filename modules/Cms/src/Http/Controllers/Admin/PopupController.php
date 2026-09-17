<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\UpsertPopupData;
use Commerce\Cms\Http\Requests\UpsertPopupRequest;
use Commerce\Cms\Models\Popup;
use Commerce\Cms\Services\PopupService;
use Commerce\Cms\Support\CmsMediaThumbnails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PopupController extends Controller
{
    use RedirectsAfterSave;

    public function __construct(
        private readonly PopupService $popups,
        private readonly CmsMediaThumbnails $thumbnails,
    ) {}

    public function index(): View
    {
        $items = Popup::query()->orderBy('priority')->orderByDesc('id')->paginate(25);

        return view('cms::admin.popups.index', [
            'items' => $items,
            'thumbnails' => $this->thumbnails->urls($items),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.popups.create');
    }

    public function store(UpsertPopupRequest $request): RedirectResponse
    {
        $item = $this->popups->create($this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.popups.index',
            route('admin.cms.popups.edit', $item),
            'Popup created.',
        );
    }

    public function edit(Popup $popup): View
    {
        return view('cms::admin.popups.edit', ['item' => $popup]);
    }

    public function update(UpsertPopupRequest $request, Popup $popup): RedirectResponse
    {
        $this->popups->update($popup, $this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.popups.index',
            route('admin.cms.popups.edit', $popup),
            'Popup saved.',
        );
    }

    public function destroy(Popup $popup): RedirectResponse
    {
        $this->popups->delete($popup);

        return redirect()->route('admin.cms.popups.index')->with('status', 'Popup deleted.');
    }

    private function toData(UpsertPopupRequest $request): UpsertPopupData
    {
        $image = $request->validated('image_media_uuid');
        $autoClose = $request->validated('auto_close');

        return new UpsertPopupData(
            title: $request->validated('title'),
            slug: $request->validated('slug'),
            status: $request->validated('status'),
            priority: (int) ($request->validated('priority') ?? 0),
            isActive: $request->boolean('is_active'),
            headline: $this->nullableString($request->validated('headline')),
            subheadline: $this->nullableString($request->validated('subheadline')),
            imageMediaUuid: is_string($image) && $image !== '' ? $image : null,
            buttonText: $this->nullableString($request->validated('button_text')),
            buttonUrl: $this->nullableString($request->validated('button_url')),
            buttonTarget: $request->validated('button_target'),
            popupType: $request->validated('popup_type'),
            showDelay: (int) ($request->validated('show_delay') ?? 0),
            autoClose: is_numeric($autoClose) ? (int) $autoClose : null,
            closable: $request->boolean('closable'),
            startAt: $this->nullableString($request->validated('start_at')),
            endAt: $this->nullableString($request->validated('end_at')),
            timezone: $request->validated('timezone'),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
