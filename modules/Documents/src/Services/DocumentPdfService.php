<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Support\DocumentFont;
use Commerce\Documents\Support\DocumentPayloadView;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * On-demand PDF cache. Files live at {pdf_directory}/{uuid}.pdf.
 *
 * v1 never deletes or voids documents, so cached files are kept.
 *
 * TODO(void / Documents v1.3): keep the original PDF; do not regenerate;
 * do not call forget() — the issued snapshot remains the legal artifact.
 * TODO(hard-delete): call forget() so the cache cannot outlive the row.
 * TODO(tenant-removed): purge files for that tenant's document UUIDs; prefer
 * tenant-prefixed paths {pdf_directory}/{tenant_id}/{uuid}.pdf before then.
 */
final class DocumentPdfService extends BaseService
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function ensureGenerated(Document $document, ?int $createdBy = null): Document
    {
        $disk = $this->disk();
        $path = is_string($document->pdf_path) && $document->pdf_path !== ''
            ? $document->pdf_path
            : $this->pathFor($document);

        if ($disk->exists($path)) {
            if ($document->pdf_path !== $path) {
                $document->forceFill(['pdf_path' => $path])->save();
            }

            return $document->fresh() ?? $document;
        }

        $disk->put($path, $this->render($document));
        $document->forceFill(['pdf_path' => $path])->save();

        $this->documents->recordEvent($document, DocumentEvent::PDF_GENERATED, $createdBy, [
            'path' => $path,
        ]);

        return $document->fresh() ?? $document;
    }

    public function contents(Document $document): string
    {
        $path = $document->pdf_path;

        if (! is_string($path) || $path === '' || ! $this->disk()->exists($path)) {
            $document = $this->ensureGenerated($document);

            return (string) $this->disk()->get((string) $document->pdf_path);
        }

        return (string) $this->disk()->get($path);
    }

    public function pathFor(Document $document): string
    {
        $directory = trim((string) config('documents.pdf_directory', 'documents'), '/');

        // TODO(tenant-removed): prefix with tenant_id when tenant purge lands.
        return $directory.'/'.$document->uuid.'.pdf';
    }

    /**
     * Remove cached PDF bytes and clear documents.pdf_path.
     * Does not mutate payload or status. Not used by v1 issue/view/download.
     */
    public function forget(Document $document): Document
    {
        $disk = $this->disk();
        $paths = array_unique(array_filter([
            is_string($document->pdf_path) && $document->pdf_path !== '' ? $document->pdf_path : null,
            $this->pathFor($document),
        ]));

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $document->forceFill(['pdf_path' => null])->save();

        return $document->fresh() ?? $document;
    }

    public function render(Document $document): string
    {
        $fontPath = DocumentFont::path();
        $boldPath = DocumentFont::boldPath();

        if (! is_file($fontPath)) {
            throw new RuntimeException('Bundled document font is missing.');
        }

        $html = view($document->type->printView(), [
            'view' => DocumentPayloadView::fromDocument($document),
            'mode' => 'pdf',
        ])->render();

        $fontDir = storage_path('app/dompdf/fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', DocumentFont::family());
        $options->set('fontDir', $fontDir);
        $options->set('fontCache', $fontDir);
        $options->setChroot([
            base_path(),
            storage_path(),
            dirname($fontPath),
            dirname($boldPath),
        ]);

        $dompdf = new Dompdf($options);
        $metrics = $dompdf->getFontMetrics();
        $registered = $metrics->registerFont(
            ['family' => DocumentFont::family(), 'style' => 'normal', 'weight' => 'normal'],
            $fontPath,
        );
        $registeredBold = $metrics->registerFont(
            ['family' => DocumentFont::family(), 'style' => 'normal', 'weight' => 'bold'],
            $boldPath,
        );

        if ($registered === false || $registeredBold === false) {
            throw new RuntimeException('Unable to register the Thai document font.');
        }
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('documents.pdf_disk', 'local'));
    }
}
