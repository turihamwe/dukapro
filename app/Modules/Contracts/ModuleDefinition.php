<?php

namespace App\Modules\Contracts;

use App\Models\Business;

interface ModuleDefinition
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /**
     * Whether this module should be enabled when a business is first created.
     */
    public function defaultEnabledFor(Business $business): bool;

    /**
     * Default sub-settings stored in business_modules.settings (JSON).
     */
    public function defaultSettingsFor(Business $business): array;
}
