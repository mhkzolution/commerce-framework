<?php

declare(strict_types=1);

namespace Commerce\Reports\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportCsvExporter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
