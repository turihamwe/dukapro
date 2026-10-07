<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AffiliateReferredBusinessActivityService
{
    /**
     * Rank referred businesses by total catalog size (parent products, all time).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Business>  $businesses
     * @return array{
     *     featured: ?array<string, mixed>,
     *     leaders: \Illuminate\Support\Collection<int, array<string, mixed>>
     * }
     */
    public function leaderboard(Collection $businesses, int $limit = 5): array
    {
        $limit = max(1, $limit);

        if ($businesses->isEmpty()) {
            return [
                'featured' => null,
                'leaders' => collect(),
            ];
        }

        $ids = $businesses->pluck('id')->map(fn ($id) => (int) $id)->all();
        $statsByBusiness = $this->totalCatalogByBusiness($ids);

        $rows = $businesses->map(function ($business) use ($statsByBusiness) {
            $stat = $statsByBusiness->get($business->id);
            $totalProducts = $stat ? (int) $stat->total_products : 0;
            $lastProductAt = $stat && $stat->last_product_at
                ? Carbon::parse($stat->last_product_at)
                : null;

            return [
                'business' => $business,
                'total_products' => $totalProducts,
                'last_product_at' => $lastProductAt,
            ];
        })
            ->filter(fn (array $row) => $row['total_products'] > 0)
            ->sortByDesc(function (array $row) {
                return sprintf(
                    '%010d-%s',
                    $row['total_products'],
                    $row['last_product_at'] ? $row['last_product_at']->timestamp : 0
                );
            })
            ->values()
            ->take($limit)
            ->values();

        return [
            'featured' => $rows->first(),
            'leaders' => $rows,
        ];
    }

    protected function totalCatalogByBusiness(array $businessIds): Collection
    {
        if ($businessIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('business_id', $businessIds)
            ->whereNull('parent_id')
            ->selectRaw('business_id, COUNT(*) as total_products, MAX(created_at) as last_product_at')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');
    }
}
