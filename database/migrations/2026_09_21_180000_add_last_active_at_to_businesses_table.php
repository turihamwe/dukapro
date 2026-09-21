<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastActiveAtToBusinessesTable extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('trial_ends_at');
            $table->index(['subscription_status', 'last_active_at']);
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'last_active_at']);
            $table->dropColumn('last_active_at');
        });
    }
}
