<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CsvEntityImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierCsvExportService
{
    public const HEADERS = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'opening_balance',
        'notes',
    ];

    public const CHUNK_SIZE = 200;

    public function download(Business $business, User $user): StreamedResponse
    {
        $filename = 'suppliers-export-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($business) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, self::HEADERS);

            Supplier::query()
                ->where('business_id', $business->id)
                ->orderBy('name')
                ->chunkById(self::CHUNK_SIZE, function ($suppliers) use ($handle) {
                    foreach ($suppliers as $supplier) {
                        fputcsv($handle, $this->formatRow($supplier));
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string>
     */
    protected function formatRow(Supplier $supplier): array
    {
        $contactPerson = '';
        $notes = $supplier->notes ?? '';
        if (preg_match('/^Contact:\s*(.+)$/mi', $notes, $matches)) {
            $contactPerson = trim($matches[1]);
        }

        return [
            $supplier->name,
            $contactPerson,
            $supplier->phone ?? '',
            $supplier->email ?? '',
            '',
            number_format((float) ($supplier->opening_balance ?? 0), 2, '.', ''),
            $notes,
        ];
    }

    public function templateCsvContents(): string
    {
        return CsvEntityImport::templateLines(self::HEADERS, [
            'Acme Supplies',
            'John Buyer',
            '0700987654',
            'buyer@acme.example',
            'Industrial Area',
            '0',
            '',
        ]);
    }
}
