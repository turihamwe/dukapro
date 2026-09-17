<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

class SeedDivisibleProductsSystemSetting extends Migration
{
    public function up()
    {
        if (! SystemSetting::query()->where('key', 'divisible_products_enabled')->exists()) {
            SystemSetting::query()->create([
                'key' => 'divisible_products_enabled',
                'value' => '1',
            ]);
        }

        SystemSetting::clearCache();
    }

    public function down()
    {
        SystemSetting::query()->where('key', 'divisible_products_enabled')->delete();
        SystemSetting::clearCache();
    }
}
