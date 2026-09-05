<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddWaiterRole extends Migration
{
    public function up()
    {
        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'waiter', 'affiliate', 'shareholder']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");
    }

    public function down()
    {
        DB::table('users')->where('role', 'waiter')->update(['role' => 'cashier']);

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'affiliate', 'shareholder']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");
    }
}
