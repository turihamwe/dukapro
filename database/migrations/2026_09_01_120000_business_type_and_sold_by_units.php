<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessTypeAndSoldByUnits extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'business_type')) {
                $table->string('business_type', 50)->nullable()->after('name');
            }
        });

        if (! Schema::hasTable('sold_by_units')) {
            Schema::create('sold_by_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'slug']);
                $table->unique(['business_id', 'name']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sold_by_units');

        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'business_type')) {
                $table->dropColumn('business_type');
            }
        });
    }
}
