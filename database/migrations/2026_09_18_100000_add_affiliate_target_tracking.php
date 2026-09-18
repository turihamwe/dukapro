<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAffiliateTargetTracking extends Migration
{
    public function up()
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliates', 'daily_shop_target')) {
                $table->decimal('daily_shop_target', 8, 2)->default(1)->after('commission_rate');
            }
        });

        if (! Schema::hasTable('affiliate_referrals')) {
            Schema::create('affiliate_referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->foreignId('business_id')->unique()->constrained('businesses')->cascadeOnDelete();
                $table->timestamp('referred_at');
                $table->timestamps();

                $table->index(['affiliate_id', 'referred_at']);
            });
        }

        if (Schema::hasTable('affiliate_referrals') && Schema::hasTable('businesses')) {
            DB::table('businesses')
                ->whereNotNull('sponsor_id')
                ->orderBy('id')
                ->chunk(200, function ($businesses) {
                    $rows = [];

                    foreach ($businesses as $business) {
                        $rows[] = [
                            'affiliate_id' => $business->sponsor_id,
                            'business_id' => $business->id,
                            'referred_at' => $business->created_at,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('affiliate_referrals')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down()
    {
        Schema::dropIfExists('affiliate_referrals');

        Schema::table('affiliates', function (Blueprint $table) {
            if (Schema::hasColumn('affiliates', 'daily_shop_target')) {
                $table->dropColumn('daily_shop_target');
            }
        });
    }
}
