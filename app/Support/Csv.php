<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export for list pages (opens in Excel; UTF-8 with a BOM so names survive).
 */
class Csv
{
    /**
     * Stream rows as a CSV download.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w') ?: throw new RuntimeException('Cannot open the output stream.');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, escape: '');

            foreach ($rows as $row) {
                // A leading =, +, - or @ would run as a formula in Excel; quote it as text.
                fputcsv($out, array_map(fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value, $row), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
