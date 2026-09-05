<?php

namespace App\Modules\Appointments;

use App\Models\Business;
use App\Modules\Contracts\ModuleDefinition;
use App\Modules\ModuleKeys;

class AppointmentsModule implements ModuleDefinition
{
    public function key(): string
    {
        return ModuleKeys::APPOINTMENTS;
    }

    public function label(): string
    {
        return 'Appointments (Preview)';
    }

    public function description(): string
    {
        return 'Example module showing the add-a-module pattern. Not a production feature yet.';
    }

    public function defaultEnabledFor(Business $business): bool
    {
        return false;
    }

    public function defaultSettingsFor(Business $business): array
    {
        return [];
    }
}
