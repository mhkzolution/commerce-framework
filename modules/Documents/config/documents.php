<?php

declare(strict_types=1);

return [
    'number_width' => 6,
    'default_currency' => 'THB',
    /*
     * Cached PDFs: {pdf_directory}/{uuid}.pdf on pdf_disk.
     *
     * v1 never deletes or voids documents, so files are kept indefinitely.
     * Lifecycle policy when those operations land (see DocumentPdfService):
     * - voided: keep the original PDF; do not regenerate; do not call forget()
     * - hard-deleted: call forget() so the cache cannot outlive the row
     * - tenant removed: purge that tenant's cached files (prefer tenant-prefixed
     *   paths: {pdf_directory}/{tenant_id}/{uuid}.pdf)
     */
    'pdf_disk' => 'local',
    'pdf_directory' => 'documents',
    'font_path' => null,
];
