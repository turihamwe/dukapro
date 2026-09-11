<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\SystemAffiliateService;
use Illuminate\Database\Seeder;

class SystemAffiliateSeeder extends Seeder
{
    public function run(): void
    {
        $affiliate = app(SystemAffiliateService::class)->ensureExists();

        Business::query()
            ->whereNull('sponsor_id')
            ->update(['sponsor_id' => $affiliate->id]);
    }
}
