<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Scopes\BranchScope;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvExportService
{
    public const CHUNK_SIZE = 200;

    /**
     * Full branch catalog export (ignores list search, stock filters, and pagination).
     */
    public function download(Business $business, User $user, ?int $branchId, ?string $branchName = null): StreamedResponse
    {
        $canViewCost = $user->can('view-cost-prices');
        $branchSlug = $branchName ? Str::slug($branchName) : 'all';
        $filename = 'products-export-' . $branchSlug . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($business, $branchId, $canViewCost) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ProductCsvImportService::REQUIRED_HEADERS);

            $this->buildQuery($business, $branchId)
                ->chunkById(self::CHUNK_SIZE, function ($products) use ($handle, $canViewCost) {
                    foreach ($products as $product) {
                        foreach ($this->rowsForCatalogProduct($product, $canViewCost) as $row) {
                            fputcsv($handle, $row);
                        }
                    }
                }, 'products.id', 'id');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function buildQuery(Business $business, ?int $branchId)
    {
        $query = Product::query()
            ->catalog()
            ->where('business_id', $business->id)
            ->with([
                'brand',
                'variants' => function ($q) {
                    $q->orderBy('name');
                },
            ])
            ->orderBy('name');

        if ($branchId) {
            $query->withoutGlobalScope(BranchScope::class)
                ->where('products.branch_id', $branchId);
        }

        return $query;
    }

    /**
     * @return list<list<string>>
     */
    protected function rowsForCatalogProduct(Product $product, bool $canViewCost): array
    {
        $category = $product->brand->name ?? '';

        if ($product->variants->isNotEmpty()) {
            $rows = [];
            foreach ($product->variants as $variant) {
                $rows[] = $this->formatRow(
                    (string) $variant->name,
                    (string) ($variant->sku ?? ''),
                    $category,
                    $variant,
                    $canViewCost
                );
            }

            return $rows;
        }

        return [
            $this->formatRow(
                (string) $product->name,
                (string) ($product->sku ?? ''),
                $category,
                $product,
                $canViewCost
            ),
        ];
    }

    /**
     * @return list<string>
     */
    protected function formatRow(string $name, string $sku, string $category, Product $product, bool $canViewCost): array
    {
        $cost = '';
        if ($canViewCost) {
            $costValue = $product->default_cost_price ?? $product->cost_price;
            if ($costValue !== null && $costValue !== '') {
                $cost = $this->formatDecimal($costValue);
            }
        }

        return [
            $name,
            $sku,
            $category,
            $cost,
            $this->formatDecimal($product->price ?? 0),
            '0',
        ];
    }

    /**
     * @param  float|int|string|null  $value
     */
    protected function formatDecimal($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
