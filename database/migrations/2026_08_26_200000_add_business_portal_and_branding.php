<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddBusinessPortalAndBranding extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('portal_slug')->nullable()->unique()->after('slug');
            $table->string('logo_path')->nullable()->after('name');
            $table->string('brand_color', 7)->default('#4f46e5')->after('logo_path');
        });

        DB::table('businesses')->orderBy('id')->get()->each(function ($business) {
            $base = Str::slug($business->name) ?: 'store';
            $portalSlug = $base . '-' . Str::lower(Str::random(8));

            while (DB::table('businesses')->where('portal_slug', $portalSlug)->exists()) {
                $portalSlug = $base . '-' . Str::lower(Str::random(8));
            }

            DB::table('businesses')->where('id', $business->id)->update([
                'portal_slug' => $portalSlug,
            ]);
        });

    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['portal_slug', 'logo_path', 'brand_color']);
        });
    }
}
