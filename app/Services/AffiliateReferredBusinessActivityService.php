<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AffiliateReferredBusinessActivityService
{
    /**
     * Rank referred businesses by recent catalog activity (new parent products).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Business>  $businesses
     * @return array{
     *     window_days: int,
     *     featured: ?array<string, mixed>,
     *     leaders: \Illuminate\Support\Collection<int, array<string, mixed>>
     * }
     */
    public function leaderboard(Collection $businesses, int $windowDays = 30, int $limit = 5): array
    {
        $windowDays = max(1, $windowDays);
        $limit = max(1, $limit);
        $since = Carbon::now()->subDays($windowDays)->startOfDay();

        if ($businesses->isEmpty()) {
            return [
                'window_days' => $windowDays,
                'featured' => null,
                'leaders' => collect(),
            ];
        }

        $ids = $businesses->pluck('id')->map(fn ($id) => (int) $id)->all();
        $statsByBusiness = $this->catalogActivitySince($ids, $since);

        $rows = $businesses->map(function ($business) use ($statsByBusiness) {
            $stat = $statsByBusiness->get($business->id);
            $productsAdded = $stat ? (int) $stat->products_added : 0;
            $lastProductAt = $stat && $stat->last_product_at
                ? Carbon::parse($stat->last_product_at)
                : null;

            return [
                'business' => $business,
                'products_added' => $productsAdded,
                'last_product_at' => $lastProductAt,
            ];
        })
            ->filter(fn (array $row) => $row['products_added'] > 0)
            ->sortByDesc(function (array $row) {
                return sprintf(
                    '%010d-%s',
                    $row['products_added'],
                    $row['last_product_at'] ? $row['last_product_at']->timestamp : 0
                );
            })
            ->values()
            ->take($limit)
            ->values();

        $featured = $rows->first();

        return [
            'window_days' => $windowDays,
            'featured' => $featured,
            'leaders' => $rows,
        ];
    }

    protected function catalogActivitySince(array $businessIds, Carbon $since): Collection
    {
        if ($businessIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('business_id', $businessIds)
            ->whereNull('parent_id')
            ->where('created_at', '>=', $since)
            ->selectRaw('business_id, COUNT(*) as products_added, MAX(created_at) as last_product_at')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');
    }
}
