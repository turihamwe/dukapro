<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetModernAsDefaultUiTheme extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'ui_theme')) {
            DB::statement("ALTER TABLE users MODIFY ui_theme VARCHAR(20) NOT NULL DEFAULT 'modern'");
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'ui_theme')) {
            DB::statement("ALTER TABLE users MODIFY ui_theme VARCHAR(20) NOT NULL DEFAULT 'plain'");
        }
    }
}
