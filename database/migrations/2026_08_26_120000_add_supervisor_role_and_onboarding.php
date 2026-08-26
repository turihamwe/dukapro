<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSupervisorRoleAndOnboarding extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'supervisor', 'cashier') NOT NULL DEFAULT 'cashier'");

        Schema::table('users', function (Blueprint $table) {
            $table->string('branch_name')->nullable()->after('role');
            $table->string('phone')->nullable()->after('email');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('sole_proprietor')->default(false)->after('is_active');
            $table->boolean('employees_onboarding_complete')->default(false)->after('sole_proprietor');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['sole_proprietor', 'employees_onboarding_complete']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branch_name', 'phone']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'manager', 'cashier') NOT NULL DEFAULT 'cashier'");
    }
}
