<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Admin;

use Commerce\Media\Contracts\MediaFolderServiceInterface;
use Commerce\Media\DTO\CreateMediaFolderData;
use Commerce\Media\DTO\UpdateMediaFolderData;
use Commerce\Media\Http\Requests\StoreMediaFolderRequest;
use Commerce\Media\Http\Requests\UpdateMediaFolderRequest;
use Commerce\Media\Models\MediaFolder;
use Commerce\Media\Services\MediaFolderQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class MediaFolderController extends Controller
{
    public function __construct(
        private readonly MediaFolderQueryService $queryService,
        private readonly MediaFolderServiceInterface $folderService,
    ) {}

    public function index(): View
    {
        return view('media::admin.folders.index', [
            'folders' => $this->queryService->flat(),
            'tree' => $this->queryService->tree(),
        ]);
    }

    public function store(StoreMediaFolderRequest $request): RedirectResponse
    {
        $this->folderService->create(new CreateMediaFolderData(
            name: $request->validated('name'),
            parentUuid: $request->validated('parent_uuid'),
        ));

        return redirect()
            ->route('admin.media.index', ['folder' => $request->validated('parent_uuid')])
            ->with('status', 'Folder created.');
    }

    public function update(UpdateMediaFolderRequest $request, string $folder): RedirectResponse
    {
        $this->folderService->update($folder, new UpdateMediaFolderData(
            name: $request->validated('name'),
            parentUuid: $request->validated('parent_uuid'),
        ));

        return redirect()
            ->route('admin.media.index', ['folder' => $folder])
            ->with('status', 'Folder updated.');
    }

    public function destroy(string $folder): RedirectResponse
    {
        $model = MediaFolder::query()->where('uuid', $folder)->firstOrFail();
        $parentUuid = $model->parent?->uuid;

        $this->folderService->delete($folder);

        return redirect()
            ->route('admin.media.index', array_filter(['folder' => $parentUuid]))
            ->with('status', 'Folder deleted.');
    }
}
