<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RepairDualRoleBusinessUsers extends Migration
{
    public function up()
    {
        // Promotion previously overwrote business users — restore business identity.
        DB::table('users')
            ->whereNotNull('business_id')
            ->where('is_affiliate', true)
            ->update(['is_affiliate' => false]);

        DB::table('users')
            ->whereNotNull('business_id')
            ->where('role', UserRole::AFFILIATE)
            ->update(['role' => UserRole::OWNER]);

        DB::table('users')
            ->whereNotNull('business_id')
            ->where('is_shareholder', true)
            ->update(['is_shareholder' => false]);

        DB::table('users')
            ->whereNotNull('business_id')
            ->where('role', UserRole::SHAREHOLDER)
            ->update(['role' => UserRole::OWNER]);
    }

    public function down()
    {
        // Irreversible data repair.
    }
}
