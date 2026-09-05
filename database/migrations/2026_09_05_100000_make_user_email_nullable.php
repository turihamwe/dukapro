<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeUserEmailNullable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
    }

    public function down()
    {
        DB::table('users')->whereNull('email')->update(['email' => DB::raw("CONCAT(username, '@no-email.local')")]);

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
    }
}
