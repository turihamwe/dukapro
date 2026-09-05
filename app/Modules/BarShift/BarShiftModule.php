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
        return 'Bar / Pub Mode';
    }

    public function description(): string
    {
        return 'For bars and pubs without a kitchen. Configure waiters and tables below — shift close always applies via the cashier.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        return $business->business_type === BusinessType::BAR_PUB;
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [
            'use_waiters' => false,
            'use_tables' => false,
        ];
    }
}
