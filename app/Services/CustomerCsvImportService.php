<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Support\CsvEntityImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerCsvImportService
{
    public const REQUIRED_HEADERS = [
        'name',
        'phone',
        'email',
        'address',
        'opening_balance',
    ];

    public const MAX_ROWS = 5000;

    public const INSERT_CHUNK = 200;

    protected CustomerService $customerService;

    protected CustomerCreditService $creditService;

    public function __construct(CustomerService $customerService, CustomerCreditService $creditService)
    {
        $this->customerService = $customerService;
        $this->creditService = $creditService;
    }

    /**
     * @return array{inserted: int, skipped_duplicates: int, skipped_errors: int, errors: list<string>}
     */
    public function import(Business $business, User $user, UploadedFile $file): array
    {
        $businessId = (int) $business->id;
        $parsed = CsvEntityImport::parseUploadedFile($file);
        CsvEntityImport::assertRequiredHeaders($parsed['headers'], self::REQUIRED_HEADERS);

        if (count($parsed['rows']) > self::MAX_ROWS) {
            throw new RuntimeException('This file has too many rows. Import up to ' . self::MAX_ROWS . ' customers at a time.');
        }

        $existingPhones = Customer::query()
            ->where('business_id', $businessId)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->map(fn ($phone) => Str::lower(trim((string) $phone)))
            ->flip();

        $seenPhones = [];
        $pending = [];
        $errors = [];
        $inserted = 0;
        $skippedDuplicates = 0;
        $skippedErrors = 0;

        foreach ($parsed['rows'] as $lineNumber => $row) {
            $rowNum = $lineNumber + 2;
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$rowNum}: name is required.";
                $skippedErrors++;
                continue;
            }

            $phone = $this->customerService->preparePhoneForStorage($row['phone'] ?? null);
            if ($phone) {
                $phoneKey = Str::lower($phone);
                if ($existingPhones->has($phoneKey) || isset($seenPhones[$phoneKey])) {
                    $skippedDuplicates++;
                    if (count($errors) < 100) {
                        $errors[] = "Row {$rowNum}: phone \"{$row['phone']}\" already exists — skipped.";
                    }
                    continue;
                }
                $seenPhones[$phoneKey] = true;
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: email is invalid.";
                $skippedErrors++;
                continue;
            }

            $openingBalance = CsvEntityImport::parseDecimal($row['opening_balance'] ?? null);
            if ($openingBalance === null || $openingBalance < 0) {
                $openingBalance = 0.0;
            }

            $creditLimit = CsvEntityImport::parseDecimal($row['credit_limit'] ?? null);
            if ($creditLimit === null || $creditLimit < 0) {
                $creditLimit = 0.0;
            }

            $terms = (int) ($row['payment_terms_days'] ?? 30);
            if ($terms < 1 || $terms > 365) {
                $terms = 30;
            }

            $pending[] = [
                'row_num' => $rowNum,
                'name' => $name,
                'company_name' => trim((string) ($row['company_name'] ?? '')) ?: null,
                'phone' => $phone,
                'email' => $email !== '' ? $email : null,
                'address' => trim((string) ($row['address'] ?? '')) ?: null,
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                'credit_limit' => $creditLimit,
                'payment_terms_days' => $terms,
                'opening_balance' => round($openingBalance, 2),
            ];
        }

        foreach (array_chunk($pending, self::INSERT_CHUNK) as $chunk) {
            $inserted += $this->insertChunk($businessId, $user, $chunk, $existingPhones, $errors);
        }

        return [
            'inserted' => $inserted,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_errors' => $skippedErrors,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     */
    protected function insertChunk(int $businessId, User $user, array $chunk, $existingPhones, array &$errors): int
    {
        $count = 0;
        $now = now();

        foreach ($chunk as $row) {
            try {
                $customer = Customer::create([
                    'business_id' => $businessId,
                    'name' => $row['name'],
                    'company_name' => $row['company_name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'notes' => $row['notes'],
                    'credit_limit' => $row['credit_limit'],
                    'payment_terms_days' => $row['payment_terms_days'],
                    'is_credit_customer' => true,
                    'is_active' => true,
                    'opening_balance' => $row['opening_balance'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($row['opening_balance'] > 0) {
                    $this->creditService->ensureOpeningBalanceEntry($user, $customer, (float) $row['opening_balance']);
                }

                if ($row['phone']) {
                    $existingPhones->put(Str::lower($row['phone']), true);
                }

                $count++;
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . $row['row_num'] . ': could not save customer.';
            }
        }

        return $count;
    }
}
