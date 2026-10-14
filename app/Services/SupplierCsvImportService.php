<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CsvEntityImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class SupplierCsvImportService
{
    public const REQUIRED_HEADERS = [
        'name',
        'phone',
        'email',
        'opening_balance',
    ];

    public const MAX_ROWS = 5000;

    public const INSERT_CHUNK = 200;

    protected SupplierCreditService $creditService;

    public function __construct(SupplierCreditService $creditService)
    {
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
            throw new RuntimeException('This file has too many rows. Import up to ' . self::MAX_ROWS . ' suppliers at a time.');
        }

        $existingNames = Supplier::query()
            ->where('business_id', $businessId)
            ->pluck('name')
            ->map(fn ($name) => Str::lower(trim((string) $name)))
            ->flip();

        $seenInFile = [];
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

            $nameKey = Str::lower($name);
            if ($existingNames->has($nameKey) || isset($seenInFile[$nameKey])) {
                $skippedDuplicates++;
                if (count($errors) < 100) {
                    $errors[] = "Row {$rowNum}: supplier \"{$name}\" already exists — skipped.";
                }
                continue;
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

            $notes = trim((string) ($row['notes'] ?? ''));
            $contactPerson = trim((string) ($row['contact_person'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));

            if ($contactPerson !== '') {
                $notes = trim('Contact: ' . $contactPerson . ($notes !== '' ? "\n" . $notes : ''));
            }
            if ($address !== '') {
                $notes = trim('Address: ' . $address . ($notes !== '' ? "\n" . $notes : ''));
            }

            $pending[] = [
                'row_num' => $rowNum,
                'name' => $name,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                'email' => $email !== '' ? $email : null,
                'notes' => $notes !== '' ? $notes : null,
                'opening_balance' => round($openingBalance, 2),
            ];

            $seenInFile[$nameKey] = true;
        }

        foreach (array_chunk($pending, self::INSERT_CHUNK) as $chunk) {
            $inserted += $this->insertChunk($businessId, $user, $chunk, $existingNames, $errors);
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
    protected function insertChunk(int $businessId, User $user, array $chunk, $existingNames, array &$errors): int
    {
        $count = 0;
        $now = now();

        foreach ($chunk as $row) {
            try {
                $supplier = Supplier::create([
                    'business_id' => $businessId,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'notes' => $row['notes'],
                    'is_active' => true,
                    'opening_balance' => $row['opening_balance'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($row['opening_balance'] > 0) {
                    $this->creditService->ensureOpeningBalanceBill($user, $supplier, (float) $row['opening_balance']);
                }

                $existingNames->put(Str::lower($row['name']), true);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . $row['row_num'] . ': could not save supplier.';
            }
        }

        return $count;
    }
}
