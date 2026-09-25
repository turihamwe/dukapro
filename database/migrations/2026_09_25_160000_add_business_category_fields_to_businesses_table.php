<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'business_category')) {
                $table->string('business_category', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('businesses', 'business_subcategory')) {
                $table->string('business_subcategory', 120)->nullable()->after('business_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'business_subcategory')) {
                $table->dropColumn('business_subcategory');
            }
            if (Schema::hasColumn('businesses', 'business_category')) {
                $table->dropColumn('business_category');
            }
        });
    }
};
