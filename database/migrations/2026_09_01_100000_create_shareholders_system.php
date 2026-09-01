<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShareholdersSystem extends Migration
{
    public function up()
    {
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->decimal('shares_owned', 8, 4)->default(0);
            $table->decimal('capital_invested', 14, 2)->default(0);
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->boolean('contract_completed')->default(false);
            $table->timestamp('contract_completed_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->text('application_message')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
            $table->index('contract_completed');
        });

        Schema::create('shareholder_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shareholder_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('description')->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['shareholder_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_shareholder')->default(false)->after('is_affiliate');
        });

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'affiliate', 'shareholder']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_shareholder');
        });

        DB::table('users')->where('role', 'shareholder')->update(['role' => 'owner']);

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'affiliate']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");

        Schema::dropIfExists('shareholder_earnings');
        Schema::dropIfExists('shareholders');
    }
}
