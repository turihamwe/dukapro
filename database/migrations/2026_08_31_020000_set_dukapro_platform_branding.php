<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SetDukaproPlatformBranding extends Migration
{
    public function up()
    {
        $now = now();

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'company_name'],
            ['value' => 'Duka Pro', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'company_tagline'],
            ['value' => "LET'S GO DIGITAL", 'created_at' => $now, 'updated_at' => $now]
        );

        Cache::forget('system_setting.company_name');
        Cache::forget('system_setting.company_tagline');

        if (class_exists(SystemSetting::class)) {
            SystemSetting::clearCache();
        }
    }

    public function down()
    {
        // Branding is safe to keep; no rollback needed.
    }
}
