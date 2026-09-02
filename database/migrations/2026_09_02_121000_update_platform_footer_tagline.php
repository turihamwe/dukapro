<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdatePlatformFooterTagline extends Migration
{
    public function up()
    {
        $legacy = [
            "Trusted by 5000+ businesses",
            "Trusted by 5000+ Businesses",
            "Manage your Business From Anywhere",
            "LET'S GO DIGITAL",
        ];

        $current = DB::table('system_settings')->where('key', 'company_tagline')->value('value');

        if ($current === null || in_array(trim((string) $current), $legacy, true)) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => 'company_tagline'],
                ['value' => 'Empowering African businesses with smart management', 'updated_at' => now()]
            );
            Cache::forget('system_setting.company_tagline');
        }
    }

    public function down()
    {
        // Safe to keep updated branding.
    }
}
