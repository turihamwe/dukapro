<?php

use App\Models\Business;
use App\Modules\ModuleKeys;
use App\Services\BusinessModuleService;
use Illuminate\Database\Migrations\Migration;

class AddFloorSettingsToBusinesses extends Migration
{
    public function up(): void
    {
        $moduleService = app(BusinessModuleService::class);

        Business::query()->orderBy('id')->chunkById(100, function ($businesses) use ($moduleService) {
            foreach ($businesses as $business) {
                $moduleService->migrateFloorSettingsFromLegacy($business);
            }
        });
    }

    public function down(): void
    {
        Business::query()->orderBy('id')->chunkById(100, function ($businesses) {
            foreach ($businesses as $business) {
                $settings = $business->settings ?? [];
                unset($settings['floor']);
                $business->update(['settings' => $settings]);
            }
        });
    }
}
