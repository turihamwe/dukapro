<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBillingFieldsForPhase6 extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('billing_grandfathered')->default(false)->after('subscription_amount');
        });

        Schema::table('business_modules', function (Blueprint $table) {
            $table->boolean('billing_comped')->default(false)->after('source');
            $table->timestamp('billing_subscribed_until')->nullable()->after('billing_comped');
        });

        DB::table('businesses')->update(['billing_grandfathered' => true]);
    }

    public function down(): void
    {
        Schema::table('business_modules', function (Blueprint $table) {
            $table->dropColumn(['billing_comped', 'billing_subscribed_until']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('billing_grandfathered');
        });
    }
}
