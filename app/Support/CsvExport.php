<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
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
