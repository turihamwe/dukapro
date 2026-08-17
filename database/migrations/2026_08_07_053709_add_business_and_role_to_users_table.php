<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessAndRoleToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->enum('role', UserRole::all())
                ->default(UserRole::CASHIER)
                ->after('business_id');

            $table->boolean('is_active')->default(true)->after('role');

            $table->index(['business_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id', 'role']);
            $table->dropColumn(['business_id', 'role', 'is_active']);
        });
    }
}
