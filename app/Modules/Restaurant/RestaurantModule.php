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
        return 'Kitchen workflow, waiter orders, and digital order routing.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        if (! BusinessType::isHospitality($business->business_type)) {
            return false;
        }

        return true;
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [
            'use_tables' => false,
        ];
    }
}
