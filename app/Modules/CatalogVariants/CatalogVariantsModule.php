<?php

namespace App\Modules\CatalogVariants;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Modules\Contracts\ModuleDefinition;
use App\Modules\ModuleKeys;

class CatalogVariantsModule implements ModuleDefinition
{
    public function key(): string
    {
        return ModuleKeys::CATALOG_VARIANTS;
    }

    public function label(): string
    {
        return 'Variant Catalog';
    }

    public function description(): string
    {
        return 'Size, color, and other product variants on inventory and sales.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        return $business->business_type === BusinessType::BOUTIQUE;
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [];
    }
}
