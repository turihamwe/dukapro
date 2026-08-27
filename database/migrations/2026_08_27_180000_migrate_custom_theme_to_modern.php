<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateCustomThemeToModern extends Migration
{
    public function up()
    {
        DB::table('users')->where('ui_theme', 'custom')->update(['ui_theme' => 'modern']);
    }

    public function down()
    {
        DB::table('users')->where('ui_theme', 'modern')->update(['ui_theme' => 'custom']);
    }
}
