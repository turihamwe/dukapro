<?php

namespace App\Services;

use App\Enums\CatalogItemType;
use App\Models\Brand;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductCsvImportService
{
    public const REQUIRED_HEADERS = [
        'name',
        'sku',
        'category',
        'cost_price',
        'selling_price',
        'initial_stock',
    ];

    public const MAX_ROWS = 5000;

    public const INSERT_CHUNK = 250;

    /** @var array<string, int> */
    protected array $brandIdCache = [];

    /**
     * @return array{inserted: int, skipped_duplicates: int, skipped_errors: int, errors: list<string>}
     */
    public function import(Business $business, User $user, UploadedFile $file, ?int $defaultBranchId): array
    {
        $businessId = (int) $business->id;
        $parsed = $this->parseCsv($file);
        $this->assertRequiredHeaders($parsed['headers']);

        if (count($parsed['rows']) > self::MAX_ROWS) {
            throw new RuntimeException('This file has too many rows. Import up to ' . self::MAX_ROWS . ' products at a time.');
        }

        $branchNames = DB::table('branches')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [Str::lower(trim($name)) => (int) $id]);

        $hasBranches = $branchNames->isNotEmpty();

        $existingSkus = DB::table('products')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->whereNotNull('sku')
            ->pluck('sku')
            ->map(fn ($sku) => Str::lower(trim((string) $sku)))
            ->flip();

        $seenInFile = [];
        $pending = [];
        $errors = [];
        $inserted = 0;
        $skippedDuplicates = 0;
        $skippedErrors = 0;
        $canSetCost = $user->can('view-cost-prices');

        foreach ($parsed['rows'] as $lineNumber => $row) {
            $rowNum = $lineNumber + 2;

            $name = trim((string) ($row['name'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$rowNum}: name is required.";
                $skippedErrors++;
                continue;
            }

            if ($sku === '') {
                $errors[] = "Row {$rowNum}: sku is required.";
                $skippedErrors++;
                continue;
            }

            $skuKey = Str::lower($sku);

            if ($existingSkus->has($skuKey) || isset($seenInFile[$skuKey])) {
                $skippedDuplicates++;
                if (count($errors) < 100) {
                    $errors[] = "Row {$rowNum}: SKU \"{$sku}\" already exists — skipped.";
                }
                continue;
            }

            $sellingPrice = $this->parseDecimal($row['selling_price'] ?? null);
            if ($sellingPrice === null || $sellingPrice < 0) {
                $errors[] = "Row {$rowNum}: selling_price must be a number ≥ 0.";
                $skippedErrors++;
                continue;
            }

            $costPrice = $this->parseDecimal($row['cost_price'] ?? null);
            if ($costPrice !== null && $costPrice < 0) {
                $errors[] = "Row {$rowNum}: cost_price must be a number ≥ 0.";
                $skippedErrors++;
                continue;
            }

            $initialStock = $this->parseDecimal($row['initial_stock'] ?? null);
            if ($initialStock === null || $initialStock < 0) {
                $errors[] = "Row {$rowNum}: initial_stock must be a number ≥ 0.";
                $skippedErrors++;
                continue;
            }

            $branchId = $defaultBranchId;
            $branchCell = trim((string) ($row['branch'] ?? ''));
            if ($branchCell !== '') {
                $resolved = $branchNames->get(Str::lower($branchCell));
                if (! $resolved) {
                    $errors[] = "Row {$rowNum}: unknown branch \"{$branchCell}\".";
                    $skippedErrors++;
                    continue;
                }
                $branchId = $resolved;
            }

            if ($hasBranches && ! $branchId) {
                $errors[] = "Row {$rowNum}: assign a branch (CSV column or import form).";
                $skippedErrors++;
                continue;
            }

            $category = trim((string) ($row['category'] ?? ''));
            $brandId = $category !== '' ? $this->resolveBrandId($businessId, $category) : null;

            $now = now();
            $pending[] = [
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'brand_id' => $brandId,
                'parent_id' => null,
                'name' => $name,
                'sku' => $sku,
                'description' => null,
                'price' => $sellingPrice,
                'cost_price' => $canSetCost ? $costPrice : null,
                'default_cost_price' => $canSetCost ? $costPrice : null,
                'inventory_cost_price' => $canSetCost ? $costPrice : null,
                'measurement_unit' => 'piece',
                'stock_quantity' => $initialStock,
                'is_active' => true,
                'is_sellable' => true,
                'is_service' => false,
                'catalog_item_type' => CatalogItemType::PHYSICAL,
                'created_at' => $now,
                'updated_at' => $now,
                '_sku_key' => $skuKey,
                '_row_num' => $rowNum,
            ];

            $seenInFile[$skuKey] = true;
        }

        foreach (array_chunk($pending, self::INSERT_CHUNK) as $chunk) {
            $inserted += $this->insertChunk($businessId, $chunk, $errors);
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
    protected function insertChunk(int $businessId, array $chunk, array &$errors): int
    {
        $rows = array_map(function (array $row) {
            unset($row['_sku_key'], $row['_row_num']);

            return $row;
        }, $chunk);

        try {
            DB::table('products')->insert($rows);
        } catch (\Throwable $exception) {
            foreach ($chunk as $row) {
                $errors[] = 'Row ' . $row['_row_num'] . ': could not save (database error).';
            }

            return 0;
        }

        $skus = array_map(fn (array $row) => $row['sku'], $chunk);
        $products = DB::table('products')
            ->where('business_id', $businessId)
            ->whereIn('sku', $skus)
            ->whereNull('deleted_at')
            ->get(['id', 'sku', 'measurement_unit', 'price']);

        $units = [];
        $now = now();
        foreach ($products as $product) {
            $units[] = [
                'product_id' => $product->id,
                'unit_name' => $product->measurement_unit,
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'price' => $product->price,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($units !== []) {
            DB::table('product_units')->insert($units);
        }

        return count($chunk);
    }

    protected function resolveBrandId(int $businessId, string $categoryName): int
    {
        $normalized = Str::lower(trim($categoryName));
        $cacheKey = $businessId . ':' . $normalized;

        if (isset($this->brandIdCache[$cacheKey])) {
            return $this->brandIdCache[$cacheKey];
        }

        $existingId = DB::table('brands')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->value('id');

        if ($existingId) {
            $this->brandIdCache[$cacheKey] = (int) $existingId;

            return $this->brandIdCache[$cacheKey];
        }

        $id = (int) DB::table('brands')->insertGetId([
            'business_id' => $businessId,
            'name' => trim($categoryName),
            'slug' => Brand::uniqueSlug($businessId, $categoryName),
            'description' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->brandIdCache[$cacheKey] = $id;

        return $id;
    }

    protected function parseDecimal(mixed $value): ?float
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

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function parseCsv(UploadedFile $file): array
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
     * @param  list<string>  $headers
     */
    protected function assertRequiredHeaders(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing !== []) {
            throw new RuntimeException(
                'Missing CSV columns: ' . implode(', ', $missing) . '. Download the template and use those headers.'
            );
        }
    }

    public function templateCsvContents(): string
    {
        $lines = [
            implode(',', self::REQUIRED_HEADERS) . ',branch',
            'Sample Widget,SKU-001,General,1000,1500,25,',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }
}
