<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'default_currency_symbol', 'value' => 'UGX', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'default_currency_position', 'value' => 'prefix', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_email', 'value' => 'support@dukapro.test', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'maintenance_mode', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_name', 'value' => 'Duka Pro', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'company_tagline', 'value' => "LET'S GO DIGITAL", 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}
