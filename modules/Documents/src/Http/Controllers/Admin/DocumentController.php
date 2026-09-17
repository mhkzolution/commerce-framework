<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Controllers\Admin;

use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Services\DocumentPdfService;
use Commerce\Documents\Services\DocumentQueryService;
use Commerce\Documents\Services\DocumentService;
use Commerce\Documents\Support\DocumentPayloadView;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentQueryService $query,
        private readonly DocumentPdfService $pdf,
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        return view('documents::admin.index', [
            'documents' => $this->query->paginate(
                search: $request->string('search')->toString() ?: null,
                type: $request->string('type')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
            ),
            'types' => $this->typeLabels(),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function show(Document $document): View
    {
        return view('documents::admin.show', [
            'document' => $document,
            'view' => DocumentPayloadView::fromDocument($document),
        ]);
    }

    public function print(Request $request, Document $document): View
    {
        $this->documents->recordEvent(
            $document,
            DocumentEvent::PRINTED,
            $this->actorId($request),
        );

        return view($document->type->printView(), [
            'view' => DocumentPayloadView::fromDocument($document),
            'mode' => 'print',
        ]);
    }

    public function download(Request $request, Document $document): Response
    {
        $actorId = $this->actorId($request);
        $document = $this->pdf->ensureGenerated($document, $actorId);
        $this->documents->recordEvent($document, DocumentEvent::DOWNLOADED, $actorId);

        return response($this->pdf->contents($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$document->number.'.pdf"',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        $labels = [];

        foreach (DocumentType::cases() as $type) {
            $labels[$type->value] = __('documents::admin.types.'.$type->value);
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        $labels = [];

        foreach (DocumentStatus::cases() as $status) {
            $labels[$status->value] = __('documents::admin.statuses.'.$status->value);
        }

        return $labels;
    }

    private function actorId(Request $request): ?int
    {
        $user = $request->user();

        return is_object($user) && isset($user->id) ? (int) $user->id : null;
    }
}
