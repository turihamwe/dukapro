<?php

use App\Services\ProductBranchBackfillService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ProductBranchBackfillService::class)->backfill();
    }

    public function down(): void
    {
        // Non-destructive: branch assignments are left in place.
    }
};
