<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddUsernameAndUiThemeToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('ui_theme', 20)->default('plain')->after('branch_name');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            $base = Str::slug($user->name ?: Str::before((string) $user->email, '@')) ?: 'user';
            $username = $base;
            $counter = 1;

            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base . $counter;
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                'ui_theme' => 'plain',
            ]);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'ui_theme']);
        });
    }
}
