<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatesSystem extends Migration
{
    public function up()
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('code', 32)->unique();
            $table->decimal('commission_rate', 5, 4)->default(0.1000);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->text('application_message')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('sponsor_id')->nullable()->after('id')->constrained('affiliates')->nullOnDelete();
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('payment_amount', 12, 2);
            $table->decimal('commission_rate', 5, 4);
            $table->decimal('commission_amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_affiliate')->default(false)->after('is_sub_admin');
        });

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'affiliate']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_affiliate');
        });

        DB::table('users')->where('role', 'affiliate')->update(['role' => 'owner']);

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");

        Schema::dropIfExists('affiliate_commissions');

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
        });

        Schema::dropIfExists('affiliates');
    }
}
