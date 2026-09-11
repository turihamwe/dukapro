<?php

use App\Models\Business;
use App\Services\SystemAffiliateService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $adminAffiliate = app(SystemAffiliateService::class)->ensureExists();

        Business::query()
            ->whereNull('sponsor_id')
            ->update(['sponsor_id' => $adminAffiliate->id]);
    }

    public function down(): void
    {
        // Non-destructive: leave assigned sponsors in place.
    }
};
