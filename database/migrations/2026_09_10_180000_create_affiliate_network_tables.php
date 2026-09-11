<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (! Schema::hasColumn('affiliates', 'parent_affiliate_id')) {
                $table->foreignId('parent_affiliate_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('affiliates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('affiliates', 'wallet_balance')) {
                $table->decimal('wallet_balance', 12, 2)->default(0)->after('commission_rate');
            }
        });

        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'referring_affiliate_id')) {
                $table->foreignId('referring_affiliate_id')
                    ->nullable()
                    ->after('sponsor_id')
                    ->constrained('affiliates')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('affiliate_withdrawal_requests')) {
            Schema::create('affiliate_withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('status', 20)->default('pending');
                $table->string('payout_method', 50)->nullable();
                $table->string('payout_account', 120)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('affiliate_team_payouts')) {
            Schema::create('affiliate_team_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->foreignId('sub_affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_team_payouts');
        Schema::dropIfExists('affiliate_withdrawal_requests');

        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'referring_affiliate_id')) {
                $table->dropConstrainedForeignId('referring_affiliate_id');
            }
        });

        Schema::table('affiliates', function (Blueprint $table) {
            if (Schema::hasColumn('affiliates', 'parent_affiliate_id')) {
                $table->dropConstrainedForeignId('parent_affiliate_id');
            }
            if (Schema::hasColumn('affiliates', 'wallet_balance')) {
                $table->dropColumn('wallet_balance');
            }
        });
    }
};
