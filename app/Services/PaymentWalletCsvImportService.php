<?php

namespace App\Services;

use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\User;
use App\Support\CsvEntityImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentWalletCsvImportService
{
    public const REQUIRED_HEADERS = [
        'name',
        'type',
    ];

    public const MAX_ROWS = 500;

    public const INSERT_CHUNK = 100;

    protected PaymentWalletService $walletService;

    public function __construct(PaymentWalletService $walletService)
    {
        $this->walletService = $walletService;
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
            throw new RuntimeException('This file has too many rows. Import up to ' . self::MAX_ROWS . ' wallets at a time.');
        }

        $existingKeys = PaymentWallet::query()
            ->where('business_id', $businessId)
            ->get(['name', 'type'])
            ->mapWithKeys(fn (PaymentWallet $wallet) => [
                $this->duplicateKey($wallet->name, $wallet->type) => true,
            ]);

        $seenInFile = [];
        $pending = [];
        $errors = [];
        $inserted = 0;
        $skippedDuplicates = 0;
        $skippedErrors = 0;

        $allowedTypes = array_keys(PaymentWallet::types());

        foreach ($parsed['rows'] as $lineNumber => $row) {
            $rowNum = $lineNumber + 2;
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$rowNum}: name is required.";
                $skippedErrors++;
                continue;
            }

            $type = Str::slug(trim((string) ($row['type'] ?? '')), '_');
            if ($type === 'mobile-money') {
                $type = PaymentWallet::TYPE_MOBILE_MONEY;
            }
            if (! in_array($type, $allowedTypes, true)) {
                $errors[] = "Row {$rowNum}: type must be one of: " . implode(', ', $allowedTypes) . '.';
                $skippedErrors++;
                continue;
            }

            $dupKey = $this->duplicateKey($name, $type);
            if ($existingKeys->has($dupKey) || isset($seenInFile[$dupKey])) {
                $skippedDuplicates++;
                if (count($errors) < 100) {
                    $errors[] = "Row {$rowNum}: wallet \"{$name}\" ({$type}) already exists — skipped.";
                }
                continue;
            }

            $openingBalance = CsvEntityImport::parseDecimal($row['opening_balance'] ?? null);
            if ($openingBalance === null || $openingBalance < 0) {
                $openingBalance = 0.0;
            }

            $sortOrder = (int) ($row['sort_order'] ?? 0);
            if ($sortOrder < 0) {
                $sortOrder = 0;
            }

            $pending[] = [
                'row_num' => $rowNum,
                'name' => $name,
                'type' => $type,
                'opening_balance' => round($openingBalance, 2),
                'sort_order' => $sortOrder,
            ];

            $seenInFile[$dupKey] = true;
        }

        foreach (array_chunk($pending, self::INSERT_CHUNK) as $chunk) {
            $inserted += $this->insertChunk($user, $chunk, $existingKeys, $errors);
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
    protected function insertChunk(User $user, array $chunk, $existingKeys, array &$errors): int
    {
        $count = 0;

        foreach ($chunk as $row) {
            try {
                $this->walletService->create($user, [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'opening_balance' => $row['opening_balance'],
                    'sort_order' => $row['sort_order'],
                ]);

                $existingKeys->put($this->duplicateKey($row['name'], $row['type']), true);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . $row['row_num'] . ': could not save wallet.';
            }
        }

        return $count;
    }

    protected function duplicateKey(string $name, string $type): string
    {
        return Str::lower(trim($name)) . ':' . $type;
    }
}
