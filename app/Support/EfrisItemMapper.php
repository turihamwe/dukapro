<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SaleItem;

class EfrisItemMapper
{
    public static function itemCode(SaleItem $item, ?Product $product): string
    {
        if ($product && filled($product->efris_item_code)) {
            return (string) $product->efris_item_code;
        }

        if (filled($item->sku)) {
            return (string) $item->sku;
        }

        if ($product && $product->isService()) {
            $fallback = config('efris.default_service_item_code');

            return filled($fallback) ? (string) $fallback : $item->product_name;
        }

        return $item->product_name;
    }

    public static function unitOfMeasure(SaleItem $item, ?Product $product): string
    {
        if ($product && $product->isService()) {
            return config('efris.service_unit', 'SV');
        }

        $key = strtolower((string) ($item->measurement_unit ?? 'piece'));

        return config('efris.unit_map.' . $key, config('efris.default_unit', 'PCE'));
    }

    public static function itemType(?Product $product): string
    {
        $kind = ($product && $product->isService()) ? 'service' : 'goods';

        return (string) config('efris.item_type.' . $kind, '1');
    }
}
