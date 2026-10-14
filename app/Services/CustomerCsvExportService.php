<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Support\CsvEntityImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerCsvExportService
{
    public const HEADERS = [
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'payment_terms_days',
        'opening_balance',
        'notes',
    ];

    public const CHUNK_SIZE = 200;

    public function download(Business $business, User $user): StreamedResponse
    {
        $filename = 'customers-export-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($business) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, self::HEADERS);

            Customer::query()
                ->where('business_id', $business->id)
                ->where('is_credit_customer', true)
                ->orderBy('name')
                ->chunkById(self::CHUNK_SIZE, function ($customers) use ($handle) {
                    foreach ($customers as $customer) {
                        fputcsv($handle, $this->formatRow($customer));
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
    protected function formatRow(Customer $customer): array
    {
        return [
            $customer->name,
            $customer->company_name ?? '',
            $customer->phone ?? '',
            $customer->email ?? '',
            $customer->address ?? '',
            number_format((float) ($customer->credit_limit ?? 0), 2, '.', ''),
            (string) ($customer->payment_terms_days ?? 30),
            number_format((float) ($customer->opening_balance ?? 0), 2, '.', ''),
            $customer->notes ?? '',
        ];
    }

    public function templateCsvContents(): string
    {
        return CsvEntityImport::templateLines(self::HEADERS, [
            'Jane Customer',
            'Jane Ltd',
            '0700123456',
            'jane@example.com',
            'Kampala Road',
            '500000',
            '30',
            '0',
            '',
        ]);
    }
}
