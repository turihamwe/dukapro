<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

class EnableSupplierCreditPlatformByDefault extends Migration
{
    public function up()
    {
        if (! class_exists(SystemSetting::class)) {
            return;
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => 'supplier_credit_platform_enabled'],
            ['value' => '1']
        );
    }

    public function down()
    {
        // Intentionally leave tenant choice intact; superadmin can disable manually.
    }
}
