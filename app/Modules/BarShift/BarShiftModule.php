<?php

namespace App\Modules\BarShift;

use App\Enums\BusinessType;
use App\Models\Business;
use App\Modules\Contracts\ModuleDefinition;
use App\Modules\ModuleKeys;

class BarShiftModule implements ModuleDefinition
{
    public function key(): string
    {
        return ModuleKeys::BAR_SHIFT;
    }

    public function label(): string
    {
        return 'Bar / Shift Mode';
    }

    public function description(): string
    {
        return 'Waiter assignment at POS and shift balancing before close.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        return BusinessType::isHospitality($business->business_type);
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [];
    }
}
