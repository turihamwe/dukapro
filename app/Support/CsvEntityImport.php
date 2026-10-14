<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class CsvEntityImport
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public static function parseUploadedFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to read the CSV file.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw new RuntimeException('The CSV file is empty.');
        }

        $headers = array_map(function ($header) {
            return Str::slug(trim((string) $header), '_');
        }, $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($data[$index] ?? ''));
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<string>  $required
     * @param  list<string>  $headers
     */
    public static function assertRequiredHeaders(array $headers, array $required): void
    {
        $missing = array_diff($required, $headers);
        if ($missing !== []) {
            throw new RuntimeException(
                'Missing CSV columns: ' . implode(', ', $missing) . '. Download the template and use those headers.'
            );
        }
    }

    public static function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([',', ' '], '', trim((string) $value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public static function templateLines(array $headers, array $sampleRow): string
    {
        return implode("\r\n", [
            implode(',', $headers),
            implode(',', $sampleRow),
        ]) . "\r\n";
    }
}
