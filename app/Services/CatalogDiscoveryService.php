<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Business;
use App\Models\Product;
use App\Models\SoldByUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CatalogDiscoveryService
{
    public function minBusinessCount(): int
    {
        return (int) config('catalog.discovery_min_businesses', 2);
    }

    public function suggestedBrands(Business $business, int $limit = 10): Collection
    {
        if (! $business->business_type) {
            return collect();
        }

        $existingNames = Brand::query()
            ->where('business_id', $business->id)
            ->pluck('name')
            ->map(fn ($name) => strtolower(trim($name)));

        return DB::table('brands')
            ->join('businesses', 'businesses.id', '=', 'brands.business_id')
            ->where('businesses.business_type', $business->business_type)
            ->where('businesses.id', '!=', $business->id)
            ->where('brands.is_active', true)
            ->select('brands.name', DB::raw('COUNT(DISTINCT brands.business_id) as business_count'))
            ->groupBy('brands.name')
            ->having('business_count', '>=', $this->minBusinessCount())
            ->orderByDesc('business_count')
            ->limit($limit)
            ->get()
            ->filter(fn ($row) => ! $existingNames->contains(strtolower(trim($row->name))))
            ->values();
    }

    public function suggestedSoldByUnits(Business $business, int $limit = 10): Collection
    {
        if (! $business->business_type) {
            return collect();
        }

        $existingSlugs = SoldByUnit::query()
            ->where('business_id', $business->id)
            ->pluck('slug');

        $productSlugs = Product::query()
            ->where('business_id', $business->id)
            ->whereNotNull('measurement_unit')
            ->distinct()
            ->pluck('measurement_unit');

        $existingSlugs = $existingSlugs->merge($productSlugs)->unique();

        $fromUnits = DB::table('sold_by_units')
            ->join('businesses', 'businesses.id', '=', 'sold_by_units.business_id')
            ->where('businesses.business_type', $business->business_type)
            ->where('businesses.id', '!=', $business->id)
            ->where('sold_by_units.is_active', true)
            ->select('sold_by_units.name', 'sold_by_units.slug', DB::raw('COUNT(DISTINCT sold_by_units.business_id) as business_count'))
            ->groupBy('sold_by_units.name', 'sold_by_units.slug')
            ->having('business_count', '>=', $this->minBusinessCount())
            ->orderByDesc('business_count')
            ->limit($limit)
            ->get();

        $fromProducts = DB::table('products')
            ->join('businesses', 'businesses.id', '=', 'products.business_id')
            ->where('businesses.business_type', $business->business_type)
            ->where('businesses.id', '!=', $business->id)
            ->whereNotNull('products.measurement_unit')
            ->select('products.measurement_unit as slug', DB::raw('COUNT(DISTINCT products.business_id) as business_count'))
            ->groupBy('products.measurement_unit')
            ->having('business_count', '>=', $this->minBusinessCount())
            ->orderByDesc('business_count')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = ucfirst(str_replace(['-', '_'], ' ', $row->slug));

                return $row;
            });

        return $fromUnits
            ->merge($fromProducts)
            ->unique('slug')
            ->filter(fn ($row) => ! $existingSlugs->contains($row->slug))
            ->sortByDesc('business_count')
            ->take($limit)
            ->values();
    }
}
