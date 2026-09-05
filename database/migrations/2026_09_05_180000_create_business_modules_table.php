<?php

use App\Models\Business;
use App\Services\BusinessModuleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 64);
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->string('source', 32)->default('owner');
            $table->timestamps();

            $table->unique(['business_id', 'module_key']);
            $table->index(['business_id', 'enabled']);
        });

        if (! Schema::hasTable('businesses')) {
            return;
        }

        /** @var BusinessModuleService $service */
        $service = app(BusinessModuleService::class);

        Business::query()->orderBy('id')->chunkById(100, function ($businesses) use ($service) {
            foreach ($businesses as $business) {
                $service->syncFromLegacySettings($business, BusinessModuleService::SOURCE_MIGRATION);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_modules');
    }
};
