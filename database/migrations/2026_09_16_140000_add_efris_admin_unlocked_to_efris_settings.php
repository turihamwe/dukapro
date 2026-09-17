<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddEfrisAdminUnlockedToEfrisSettings extends Migration
{
    public function up()
    {
        Schema::table('efris_settings', function (Blueprint $table) {
            $table->boolean('efris_admin_unlocked')->default(false)->after('efris_enabled');
        });

        if (Schema::hasTable('efris_settings')) {
            DB::table('efris_settings')->update([
                'efris_enabled' => false,
                'efris_admin_unlocked' => false,
            ]);
        }
    }

    public function down()
    {
        Schema::table('efris_settings', function (Blueprint $table) {
            $table->dropColumn('efris_admin_unlocked');
        });
    }
}
