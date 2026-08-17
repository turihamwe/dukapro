<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionFieldsToBusinessesTable extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('is_active');
            $table->enum('subscription_status', SubscriptionStatus::all())
                ->default(SubscriptionStatus::TRIAL)
                ->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
            $table->decimal('subscription_amount', 10, 2)->default(1500)->after('subscription_ends_at');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at',
                'subscription_status',
                'subscription_ends_at',
                'subscription_amount',
            ]);
        });
    }
}
