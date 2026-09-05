<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_modules')) {
            return;
        }

        DB::table('business_modules')->where('module_key', 'appointments')->delete();
    }

    public function down(): void
    {
        //
    }
};
