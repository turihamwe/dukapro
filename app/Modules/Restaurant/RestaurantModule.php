<?php

namespace App\Modules\Restaurant;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Modules\Contracts\ModuleDefinition;
use App\Modules\ModuleKeys;

class RestaurantModule implements ModuleDefinition
{
    public function key(): string
    {
        return ModuleKeys::RESTAURANT;
    }

    public function label(): string
    {
        return 'Restaurant Mode';
    }

    public function description(): string
    {
        return 'Kitchen workflow: send orders to the kitchen and track ready orders. Configure waiters and tables in Floor service below.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        return $business->business_type === BusinessType::RESTAURANT;
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [
            'use_tables' => false,
            'use_waiters' => false,
        ];
    }
}
