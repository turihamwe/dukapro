<?php

namespace App\Services;

use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\User;
use App\Support\CsvEntityImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentWalletCsvExportService
{
    public const HEADERS = [
        'name',
        'type',
        'identifier',
        'opening_balance',
        'sort_order',
        'is_active',
    ];

    public const CHUNK_SIZE = 200;

    public function download(Business $business, User $user): StreamedResponse
    {
        $filename = 'wallets-export-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($business) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, self::HEADERS);

            PaymentWallet::query()
                ->where('business_id', $business->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->chunkById(self::CHUNK_SIZE, function ($wallets) use ($handle) {
                    foreach ($wallets as $wallet) {
                        fputcsv($handle, $this->formatRow($wallet));
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
    protected function formatRow(PaymentWallet $wallet): array
    {
        return [
            $wallet->name,
            $wallet->type,
            (string) $wallet->id,
            '0',
            (string) ($wallet->sort_order ?? 0),
            $wallet->is_active ? '1' : '0',
        ];
    }

    public function templateCsvContents(): string
    {
        return CsvEntityImport::templateLines(self::HEADERS, [
            'Cash at Hand',
            'cash',
            '',
            '0',
            '0',
            '1',
        ]);
    }
}
